import threading
import time
import traceback

from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.common.by import By
from webdriver_manager.chrome import ChromeDriverManager

import config
from crm_client import CrmClient

PATH = ChromeDriverManager().install()
results = {}
lock = threading.Lock()


class StepClient(CrmClient):
    def _clickable(self, css, timeout=None):
        t0 = time.time()
        try:
            r = super()._clickable(css, timeout)
            print(f"[{self.tag}] clickable {css!r} ok in {time.time()-t0:.1f}s", flush=True)
            return r
        except Exception as e:
            print(f"[{self.tag}] clickable {css!r} TIMEOUT after {time.time()-t0:.1f}s: {e}", flush=True)
            raise

    def _find(self, css, timeout=None):
        t0 = time.time()
        try:
            r = super()._find(css, timeout)
            print(f"[{self.tag}] find {css!r} ok in {time.time()-t0:.1f}s", flush=True)
            return r
        except Exception as e:
            print(f"[{self.tag}] find {css!r} TIMEOUT after {time.time()-t0:.1f}s: {e}", flush=True)
            raise

    def _fill(self, css, value):
        print(f"[{self.tag}] fill {css!r} = {value}", flush=True)
        return super()._fill(css, value)


def run(account, idx):
    opts = Options()
    opts.add_argument("--ignore-certificate-errors")
    opts.add_argument("--disable-gpu")
    opts.add_argument("--no-sandbox")
    opts.add_argument("--disable-dev-shm-usage")
    opts.add_argument("--window-size=1280,900")
    opts.add_argument("--headless=new")
    driver = webdriver.Chrome(service=Service(PATH), options=opts)
    client = StepClient(driver, timeout=30)
    client.tag = account["email"]
    try:
        client.login(account["email"], config.ACCOUNT_PASSWORD)
        print(f"[{client.tag}] logged in", flush=True)
        payload = {
            "name": f"Dbg {account['email']}-{idx}",
            "amount": 10000,
            "email": f"dbg.{account['email'].replace('@','.').replace('.','')}.{idx}@example.test",
            "first_name": "Dbg",
            "last_name": account["email"],
            "phone": "070000001",
            "consultant_name": "Dbg Agency",
            "agency_deal_value": 1000,
            "margin_agreed": 10,
        }
        deal_id = client.create_deal(payload)
        print(f"[{client.tag}] created deal {deal_id}", flush=True)
        results[account["email"]] = f"OK {deal_id}"
    except Exception as e:
        print(f"[{client.tag}] FAIL {type(e).__name__}: {e}", flush=True)
        traceback.print_exc()
        results[account["email"]] = f"FAIL {type(e).__name__}"
    finally:
        driver.quit()


threads = []
for acc in config.SALES_ACCOUNTS:
    t = threading.Thread(target=run, args=(acc, 1))
    threads.append(t)
    t.start()
for t in threads:
    t.join()

print("\n==== RESULTS ====")
for k, v in results.items():
    print(k, "->", v)
