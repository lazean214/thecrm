import threading
import time
import traceback

from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait
from webdriver_manager.chrome import ChromeDriverManager

import config
from crm_client import CrmClient

PATH = ChromeDriverManager().install()
results = {}
lock = threading.Lock()


class StepClient(CrmClient):
    def __init__(self, *a, **k):
        super().__init__(*a, **k)
        self.tag = "?"

    def _clickable(self, css, timeout=None):
        t0 = time.time()
        try:
            r = super()._clickable(css, timeout)
            self.p(f"clickable {css} ok {time.time()-t0:.1f}s")
            return r
        except Exception as e:
            self.p(f"clickable {css} TIMEOUT {time.time()-t0:.1f}s: {e}")
            raise

    def _find(self, css, timeout=None):
        t0 = time.time()
        try:
            r = super()._find(css, timeout)
            self.p(f"find {css} ok {time.time()-t0:.1f}s")
            return r
        except Exception as e:
            self.p(f"find {css} TIMEOUT {time.time()-t0:.1f}s")
            raise

    def _fill(self, css, value):
        t0 = time.time()
        try:
            r = super()._fill(css, value)
            self.p(f"fill {css} ok {time.time()-t0:.1f}s")
            return r
        except Exception as e:
            self.p(f"fill {css} FAIL {time.time()-t0:.1f}s")
            raise

    def p(self, m):
        with lock:
            print(f"[{self.tag}] {m}", flush=True)


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
        client.p("logged in")
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
        client.p(f"created deal {deal_id}")
        results[account["email"]] = f"OK {deal_id}"
    except Exception as e:
        client.p(f"FAIL {type(e).__name__}")
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
