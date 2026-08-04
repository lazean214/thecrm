import threading
import time
import traceback

from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.common.by import By
from webdriver_manager.chrome import ChromeDriverManager

import config
from crm_client import CrmClient

PATH = ChromeDriverManager().install()
lock = threading.Lock()


def probe(email):
    opts = Options()
    opts.add_argument("--ignore-certificate-errors")
    opts.add_argument("--disable-gpu")
    opts.add_argument("--headless=new")
    opts.add_argument("--window-size=1280,900")
    driver = webdriver.Chrome(service=Service(PATH), options=opts)
    client = CrmClient(driver, timeout=20)
    try:
        client.login(email, config.ACCOUNT_PASSWORD)
        driver.get(config.DEALS_URL)
        client._settle(2)
        trigger = client._clickable("button.deal-trigger-btn")
        driver.execute_script("arguments[0].click();", trigger)
        timeline = []
        for i in range(12):
            time.sleep(1)
            present = bool(driver.find_elements(By.CSS_SELECTOR, "#deal-modal"))
            timeline.append(f"t+{i+1}:{'Y' if present else '-'}")
            if present:
                break
        with lock:
            print(f"[{email}] {' '.join(timeline)}", flush=True)
    except Exception as e:
        with lock:
            print(f"[{email}] EXC {type(e).__name__}: {e}", flush=True)
    finally:
        driver.quit()


threads = []
for acc in config.SALES_ACCOUNTS:
    t = threading.Thread(target=probe, args=(acc["email"],))
    threads.append(t)
    t.start()
for t in threads:
    t.join()
print("done")
