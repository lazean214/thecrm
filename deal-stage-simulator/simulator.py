"""
Deal Stage Simulator - concurrent engine.

Launches one browser per account at the same time and drives deals through the
real CRM UI:

  Sales reps   create deals and move them  doc sent -> doc signed -> compliant
  Compliance   concurrently moves them     compliant -> ready for payment -> paid

It also verifies the permission model:
  * a sales rep cannot reach ready-for-payment / paid (buttons locked)
  * a sales rep cannot open another rep's deal (403)
  * compliance can view + advance every rep's deals
"""

import random
import threading
import time
from datetime import datetime

from selenium import webdriver
from selenium.common.exceptions import TimeoutException
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from webdriver_manager.chrome import ChromeDriverManager

import config
from crm_client import CrmClient

print_lock = threading.Lock()


def _err(e):
    return f"{type(e).__name__}: {e}"[:2000]


def log(msg):
    with print_lock:
        print(f"[{datetime.now().strftime('%H:%M:%S')}] {msg}")


# ---------------------------------------------------------------------------
# Coordination primitives
# ---------------------------------------------------------------------------

class Gate:
    """Release all waiting threads at the same instant (simultaneous start)."""

    def __init__(self, n):
        self._cv = threading.Condition()
        self._ready = 0
        self._n = n

    def wait(self):
        with self._cv:
            self._ready += 1
            if self._ready >= self._n:
                self._cv.notify_all()
            else:
                self._cv.wait(timeout=120)


class DealRegistry:
    """Thread-safe registry of deals created by sales reps."""

    def __init__(self):
        self._cv = threading.Condition()
        self._deals = {}

    def add(self, deal):
        with self._cv:
            self._deals[deal["deal_id"]] = deal
            self._cv.notify_all()

    def set_stage(self, deal_id, stage):
        with self._cv:
            if deal_id in self._deals:
                self._deals[deal_id]["stage"] = stage
            self._cv.notify_all()

    def count(self):
        with self._cv:
            return len(self._deals)

    def find_owned_by_other(self, owner_email, exclude_deal_id=None):
        with self._cv:
            for deal in self._deals.values():
                if deal["owner_email"] != owner_email and deal["deal_id"] != exclude_deal_id:
                    return deal
        return None

    def all(self):
        with self._cv:
            return list(self._deals.values())

    def wait_for_count(self, target, timeout):
        deadline = time.time() + timeout
        with self._cv:
            while self._deals.__len__() < target:
                remaining = deadline - time.time()
                if remaining <= 0:
                    return False
                self._cv.wait(remaining)
            return True


class Recorder:
    """Thread-safe result collection."""

    def __init__(self):
        self._lock = threading.Lock()
        self.results = []

    def record(self, account, role, scenario, deal_id, expected, actual, status, error=""):
        with self._lock:
            self.results.append({
                "account": account,
                "role": role,
                "scenario": scenario,
                "deal_id": deal_id,
                "expected": expected,
                "actual": actual,
                "status": status,
                "error": error,
            })


# ---------------------------------------------------------------------------
# Driver setup
# ---------------------------------------------------------------------------

_DRIVER_PATH = None
_driver_lock = threading.Lock()


def _chromedriver_path():
    """Resolve the chromedriver path once (concurrent calls share the cache)."""
    global _DRIVER_PATH
    if _DRIVER_PATH is None:
        with _driver_lock:
            if _DRIVER_PATH is None:
                _DRIVER_PATH = ChromeDriverManager().install()
    return _DRIVER_PATH


def build_driver(headless=False):
    options = Options()
    options.add_argument("--ignore-certificate-errors")
    options.add_argument("--disable-gpu")
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")
    options.add_argument("--window-size=1280,900")
    if headless:
        options.add_argument("--headless=new")
    service = Service(_chromedriver_path())
    return webdriver.Chrome(service=service, options=options)


# ---------------------------------------------------------------------------
# Payload generator
# ---------------------------------------------------------------------------

class PayloadFactory:
    def __init__(self):
        self.token = datetime.now().strftime("%Y%m%d%H%M%S")
        self._seq = 0
        self._lock = threading.Lock()

    def next(self, rep_email):
        with self._lock:
            self._seq += 1
            n = self._seq
        rep = rep_email.split("@")[0].replace(".", "")
        return {
            "name": f"Sim Deal {rep}-{n}",
            "amount": random.randint(5000, 50000),
            "email": f"sim.{self.token}.{rep}.{n}@example.test",
            "first_name": f"Sim{n}",
            "last_name": f"User{rep.upper()}",
            "phone": f"070{n:06d}",
            "consultant_name": f"Sim Agency {self.token}-{n}",
            "agency_deal_value": random.randint(1000, 8000),
            "margin_agreed": random.randint(5, 20),
        }


# ---------------------------------------------------------------------------
# Workers
# ---------------------------------------------------------------------------

def sales_worker(account, deals_per_rep, registry, recorder, gate, headless, interval, other_reps_exist):
    """One sales rep: create deals, drive them, verify permissions."""
    email = account["email"]
    client = None
    try:
        log(f"[{email}] starting browser + login")
        client = CrmClient(build_driver(headless))
        client.login(email, config.ACCOUNT_PASSWORD)
        log(f"[{email}] logged in, waiting for simultaneous start")
        gate.wait()

        factory = PayloadFactory()
        for i in range(deals_per_rep):
            payload = factory.next(email)

            # 1. Create deal
            deal_id = None
            try:
                deal_id = client.create_deal(payload)
                recorder.record(email, "sales", "create_deal", deal_id, "redirect to /deals/{id}", f"/deals/{deal_id}", "PASS")
            except Exception as e:
                recorder.record(email, "sales", "create_deal", deal_id, "redirect to /deals/{id}", "exception", "FAIL", _err(e))
                continue

            registry.add({"deal_id": deal_id, "owner_email": email, "stage": "doc sent"})

            # 2. Initial stage
            try:
                initial = client.get_current_stage()
                status = "PASS" if initial == "doc sent" else "FAIL"
                recorder.record(email, "sales", "initial_stage", deal_id, "doc sent", initial, status)
            except Exception as e:
                recorder.record(email, "sales", "initial_stage", deal_id, "doc sent", "exception", "FAIL", _err(e))

            # 3. Drive doc sent -> doc signed -> compliant
            for target in config.SALES_DRIVE:
                try:
                    client.click_stage(target)
                    client.wait_for_stage(target)
                    registry.set_stage(deal_id, target)
                    recorder.record(email, "sales", f"move:{target}", deal_id, target, target, "PASS")
                except PermissionError as e:
                    recorder.record(email, "sales", f"move:{target}", deal_id, target, "button disabled", "FAIL", _err(e))
                    break
                except Exception as e:
                    recorder.record(email, "sales", f"move:{target}", deal_id, target, "exception", "FAIL", _err(e))
                    break
                if interval:
                    time.sleep(interval)

            # 4. Locked stages for sales (permission check)
            for blocked in config.SALES_BLOCKED_STAGES:
                try:
                    enabled = client.is_stage_enabled(blocked)
                    status = "PASS" if not enabled else "FAIL"
                    recorder.record(
                        email, "sales", f"blocked:{blocked}", deal_id,
                        f"button disabled for sales", f"enabled={enabled}", status,
                    )
                except Exception as e:
                    recorder.record(email, "sales", f"blocked:{blocked}", deal_id, "button disabled for sales", "exception", "FAIL", _err(e))

        # 5. Isolation: cannot open another rep's deal (403)
        other = None
        deadline = time.time() + 60
        while time.time() < deadline:
            other = registry.find_owned_by_other(email)
            if other:
                break
            time.sleep(1)

        if other:
            try:
                client.open_deal(other["deal_id"])
                forbidden = client.is_forbidden_page()
                status = "PASS" if forbidden else "FAIL"
                recorder.record(email, "sales", "isolation_view_other", other["deal_id"], "403 Forbidden", f"forbidden={forbidden}", status)
            except Exception as e:
                recorder.record(email, "sales", "isolation_view_other", other["deal_id"], "403 Forbidden", "exception", "FAIL", _err(e))
        elif not other_reps_exist:
            recorder.record(email, "sales", "isolation_view_other", None, "403 Forbidden", "only one sales rep", "SKIP", "no other rep exists to test isolation")
        else:
            recorder.record(email, "sales", "isolation_view_other", None, "403 Forbidden", "no other-rep deal found", "FAIL", "registry empty for other reps")

    except Exception as e:
        log(f"[{email}] worker error: {e}")
        recorder.record(email, "sales", "worker", None, "no worker crash", "exception", "FAIL", _err(e))
    finally:
        if client:
            client.quit()
        log(f"[{email}] finished")


def compliance_worker(account, registry, recorder, gate, headless, interval, expected_total, wait_timeout):
    """Compliance: wait for sales deals, then advance compliant -> paid."""
    email = account["email"]
    client = None
    try:
        log(f"[{email}] starting browser + login")
        client = CrmClient(build_driver(headless))
        client.login(email, config.ACCOUNT_PASSWORD)
        log(f"[{email}] logged in, waiting for simultaneous start")
        gate.wait()

        log(f"[{email}] waiting for {expected_total} deal(s) from sales...")
        if not registry.wait_for_count(expected_total, wait_timeout):
            recorder.record(email, "compliance", "wait_for_deals", None, f"{expected_total} deals", f"{registry.count()} deals", "FAIL", "timeout waiting for sales deals")
            return

        for deal in registry.all():
            deal_id = deal["deal_id"]
            # Wait until sales has driven this deal to compliant.
            deadline = time.time() + wait_timeout
            ready = False
            while time.time() < deadline:
                try:
                    client.open_deal(deal_id)
                    if client.is_forbidden_page():
                        recorder.record(email, "compliance", "can_view", deal_id, "deal accessible", "403 forbidden", "FAIL")
                        break
                    stage = client.get_current_stage()
                    recorder.record(email, "compliance", "can_view", deal_id, "deal accessible", stage, "PASS")
                    if stage in ("compliant", "ready for payment", "paid"):
                        ready = True
                        break
                except Exception as e:
                    recorder.record(email, "compliance", "can_view", deal_id, "deal accessible", "exception", "FAIL", _err(e))
                time.sleep(2)

            if not ready:
                recorder.record(email, "compliance", "compliance_drive", deal_id, "compliant before drive", "not ready", "FAIL", "deal never reached compliant")
                continue

            for target in config.COMPLIANCE_DRIVE:
                try:
                    client.click_stage(target)
                    client.wait_for_stage(target)
                    registry.set_stage(deal_id, target)
                    recorder.record(email, "compliance", f"compliance_move:{target}", deal_id, target, target, "PASS")
                except PermissionError as e:
                    recorder.record(email, "compliance", f"compliance_move:{target}", deal_id, target, "button disabled", "FAIL", _err(e))
                    break
                except Exception as e:
                    recorder.record(email, "compliance", f"compliance_move:{target}", deal_id, target, "exception", "FAIL", _err(e))
                    break
                if interval:
                    time.sleep(interval)

    except Exception as e:
        log(f"[{email}] worker error: {e}")
        recorder.record(email, "compliance", "worker", None, "no worker crash", "exception", "FAIL", _err(e))
    finally:
        if client:
            client.quit()
        log(f"[{email}] finished")


# ---------------------------------------------------------------------------
# Orchestration
# ---------------------------------------------------------------------------

def run_simulation(sales_accounts, compliance_account, deals_per_rep, headless=False, interval=0.0, timeout=config.DEFAULT_TIMEOUT):
    registry = DealRegistry()
    recorder = Recorder()

    workers = []
    for account in sales_accounts:
        workers.append(("sales", account))
    workers.append(("compliance", compliance_account))

    gate = Gate(len(workers))
    expected_total = len(sales_accounts) * deals_per_rep
    other_reps_exist = len(sales_accounts) > 1

    threads = []
    for role, account in workers:
        if role == "sales":
            t = threading.Thread(
                target=sales_worker,
                args=(account, deals_per_rep, registry, recorder, gate, headless, interval, other_reps_exist),
                name=f"sales:{account['email']}",
            )
        else:
            t = threading.Thread(
                target=compliance_worker,
                args=(account, registry, recorder, gate, headless, interval, expected_total, timeout),
                name=f"compliance:{account['email']}",
            )
        threads.append(t)

    log(f"Launching {len(threads)} concurrent browser sessions...")
    for t in threads:
        t.start()
    for t in threads:
        t.join()
    log("All workers finished.")

    return recorder.results


def summarize(results):
    total = len(results)
    passed = sum(1 for r in results if r["status"] == "PASS")
    failed = sum(1 for r in results if r["status"] == "FAIL")
    skipped = sum(1 for r in results if r["status"] == "SKIP")
    return total, passed, failed, skipped


def print_report(results):
    total, passed, failed, skipped = summarize(results)
    sep = "=" * 78
    print("\n" + sep)
    print("DEAL STAGE SIMULATION - REPORT")
    print(sep)

    if not results:
        print("No results collected.")
        return

    by_scenario = {}
    for r in results:
        by_scenario.setdefault(r["scenario"], []).append(r)

    for scenario, items in by_scenario.items():
        sp = sum(1 for r in items if r["status"] == "PASS")
        sf = sum(1 for r in items if r["status"] == "FAIL")
        sk = len(items) - sp - sf
        marker = "OK " if sf == 0 else "!! "
        suffix = f" (+{sk} skipped)" if sk else ""
        print(f"\n{marker}{scenario:<34} {sp}/{len(items)} passed{suffix}")
        for r in items:
            if r["status"] != "PASS":
                print(f"      {r['status']} -> account={r['account']} deal={r['deal_id']} expected={r['expected']} actual={r['actual']} err={r['error']}")

    print("\n" + sep)
    summary = f"TOTAL: {passed}/{total} passed, {failed} failed"
    if skipped:
        summary += f", {skipped} skipped"
    print(summary)
    print(sep)
