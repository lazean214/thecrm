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


def probe(email):
    opts = Options()
    opts.add_argument("--ignore-certificate-errors")
    opts.add_argument("--disable-gpu")
    opts.add_argument("--headless=new")
    opts.add_argument("--window-size=1280,900")
    driver = webdriver.Chrome(service=Service(ChromeDriverManager().install()), options=opts)
    client = CrmClient(driver, timeout=20)
    try:
        client.login(email, config.ACCOUNT_PASSWORD)
        driver.get(config.DEALS_URL)
        client._settle(2)

        # Instrument Livewire error surface.
        driver.execute_script("""
            window.__lw_events = [];
            document.addEventListener('livewire:update', () => window.__lw_events.push('update'));
            document.addEventListener('livewire:load', () => window.__lw_events.push('load'));
            document.addEventListener('livewire:error', (e) => window.__lw_events.push('error:' + JSON.stringify(e.detail)));
            window.addEventListener('error', (e) => window.__lw_events.push('jserror:' + (e.message||'').slice(0,120)));
            window.addEventListener('unhandledrejection', (e) => window.__lw_events.push('rejection:' + String(e.reason).slice(0,120)));
        """)

        trigger = client._clickable("button.deal-trigger-btn")
        print(f"[{email}] trigger found; html: {trigger.get_attribute('outerHTML')[:220]}", flush=True)
        driver.execute_script("arguments[0].click();", trigger)
        print(f"[{email}] JS-clicked trigger", flush=True)

        for i in range(10):
            time.sleep(1)
            present = bool(driver.find_elements(By.CSS_SELECTOR, "#deal-modal"))
            print(f"[{email}] t+{i+1}s deal-modal present: {present}", flush=True)
            if present:
                break

        evts = driver.execute_script("return window.__lw_events || []")
        print(f"[{email}] livewire events: {evts}", flush=True)
        print(f"[{email}] final url: {driver.current_url}", flush=True)
    except Exception:
        traceback.print_exc()
    finally:
        driver.quit()


for email in ["sales@thecrm.com", "sales2@thecrm.com"]:
    probe(email)
