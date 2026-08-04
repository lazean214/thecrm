"""
Deal Stage Simulator - Selenium page objects.

Encapsulates the real CRM UI flows used by the simulator:
  * login
  * create a deal (modal form)
  * drive a deal through its stage navigator (setStage buttons)
  * read the current stage + whether a stage button is locked

The stage navigator lives on /deals/{id} and renders one button per stage with
`wire:click="setStage('<stage>')"`. Buttons a user's team cannot use are marked
`disabled` with the title "Your team cannot move deals to this stage".
"""

import re
import time

from selenium.common.exceptions import NoSuchElementException, StaleElementReferenceException, TimeoutException
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait

import config


class CrmClient:
    """A single logged-in browser session for one CRM account."""

    def __init__(self, driver, timeout=config.DEFAULT_TIMEOUT):
        self.driver = driver
        self.timeout = timeout
        self.wait = WebDriverWait(driver, timeout)

    # ------------------------------------------------------------------
    # Low-level helpers
    # ------------------------------------------------------------------
    def _settle(self, seconds=None):
        time.sleep(seconds if seconds is not None else config.LIVEWIRE_SETTLE)

    def _find(self, css, timeout=None):
        return WebDriverWait(self.driver, timeout or self.timeout).until(
            EC.presence_of_element_located((By.CSS_SELECTOR, css))
        )

    def _clickable(self, css, timeout=None):
        return WebDriverWait(self.driver, timeout or self.timeout).until(
            EC.element_to_be_clickable((By.CSS_SELECTOR, css))
        )

    def _click(self, element, css=None):
        """Click via JS dispatch.

        Livewire's Alpine-managed listeners do not reliably receive Selenium's
        synthesized native click, so we dispatch a JS click instead (verified to
        trigger wire:actions). Retries on stale nodes from Livewire re-renders.
        """
        deadline = time.time() + self.timeout
        while True:
            try:
                self.driver.execute_script("arguments[0].click();", element)
                return
            except StaleElementReferenceException:
                if css is None:
                    raise
                if time.time() > deadline:
                    raise
                element = self._clickable(css)

    def _fill(self, css, value):
        field = self._clickable(css)
        field.clear()
        field.send_keys(value)
        self._settle(0.4)

    # ------------------------------------------------------------------
    # Auth
    # ------------------------------------------------------------------
    def login(self, email, password):
        self.driver.get(config.LOGIN_URL)
        self.wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "input[type='email']")))
        self._fill("input[type='email']", email)
        self._fill("input[type='password']", password)
        self._click(self._clickable("button[type='submit']"), "button[type='submit']")

        # Wait until we leave the login page.
        WebDriverWait(self.driver, self.timeout).until(
            EC.url_changes(config.LOGIN_URL)
        )
        self._settle(config.PAGE_LOAD_PAUSE)

    # ------------------------------------------------------------------
    # Deal creation
    # ------------------------------------------------------------------
    def create_deal(self, payload):
        """Create a deal via the New Deal modal.

        Returns the new deal id once the app redirects to /deals/{id}.
        """
        self.driver.get(config.DEALS_URL)
        self._click(self._clickable("button.deal-trigger-btn"), "button.deal-trigger-btn")
        self._find("#deal-modal")

        self._fill("input[wire\\:model='name']", payload["name"])
        self._fill("input[wire\\:model='amount']", str(payload["amount"]))

        stage_select = self._find("select[wire\\:model='stage']")
        self.driver.execute_script(
            "arguments[0].value = arguments[1]; arguments[0].dispatchEvent(new Event('change', { bubbles: true }));",
            stage_select,
            "doc sent",
        )
        self._settle()

        self._fill("input[wire\\:model\\.live='email']", payload["email"])
        self._settle(0.8)

        # Existing contact banner would block the fresh-details path; skip it.
        self._fill("input[wire\\:model='first_name']", payload["first_name"])
        self._fill("input[wire\\:model='last_name']", payload["last_name"])
        self._fill("input[wire\\:model='phone']", payload["phone"])

        # Recruitment source (Inbound) -> consultant/agency autocomplete.
        source_select = self._find("select[wire\\:model\\.live='recruitment_agency']")
        self.driver.execute_script(
            "arguments[0].value = 'Inbound'; arguments[0].dispatchEvent(new Event('change', { bubbles: true }));",
            source_select,
        )
        self._settle(0.8)

        self._fill("input[wire\\:model\\.live='consultant_name']", payload["consultant_name"])
        self._settle(0.8)

        # Close any autocomplete dropdown that may have appeared.
        try:
            dropdown = self.driver.find_element(By.CSS_SELECTOR, "div.deal-autocomplete-dropdown")
            if dropdown.is_displayed():
                self.driver.execute_script("arguments[0].style.display = 'none';", dropdown)
        except NoSuchElementException:
            pass

        try:
            self._fill("input[wire\\:model='agency_deal_value']", str(payload["agency_deal_value"]))
            self._fill("input[wire\\:model='margin_agreed']", str(payload["margin_agreed"]))
        except (TimeoutException, NoSuchElementException):
            pass

        self._click(self._clickable("button[form='deal-form']"), "button[form='deal-form']")

        # Component redirects to /deals/{id} after saving.
        WebDriverWait(self.driver, self.timeout).until(
            EC.url_matches(rf"{re.escape(config.BASE_URL)}/deals/\d+")
        )
        self._settle(config.PAGE_LOAD_PAUSE)

        match = re.search(r"/deals/(\d+)", self.driver.current_url)
        if not match:
            raise RuntimeError(f"Could not extract deal id from URL: {self.driver.current_url}")
        return int(match.group(1))

    # ------------------------------------------------------------------
    # Stage navigator
    # ------------------------------------------------------------------
    def open_deal(self, deal_id):
        self.driver.get(config.DEAL_URL_TEMPLATE.format(deal_id=deal_id))
        self._settle(config.PAGE_LOAD_PAUSE)

    # Every stage button carries wire:target="setStage" (enabled, active and
    # locked alike). The stage label is the first <p> inside the button.
    STAGE_LABELS = {
        "doc sent": "Doc Sent",
        "doc signed": "Doc Signed",
        "compliant": "Compliant",
        "ready for payment": "Ready for Payment",
        "paid": "Paid",
        "lost": "Lost",
    }

    def _all_stage_buttons(self):
        return self.driver.find_elements(By.CSS_SELECTOR, "button[wire\\:target='setStage']")

    def _stage_button(self, stage, timeout=None):
        """Return the navigator button for a stage (matched by its label)."""
        label = self.STAGE_LABELS[stage]
        deadline = time.time() + (timeout or self.timeout)
        while time.time() < deadline:
            try:
                for btn in self._all_stage_buttons():
                    try:
                        first_p = btn.find_element(By.CSS_SELECTOR, "p")
                        if first_p.text.strip().lower() == label.lower():
                            return btn
                    except NoSuchElementException:
                        continue
            except StaleElementReferenceException:
                pass
            time.sleep(0.4)
        raise TimeoutError(f"Stage button for '{stage}' not found")

    def get_current_stage(self, timeout=None):
        """Return the stage currently shown as active in the navigator."""
        deadline = time.time() + (timeout or self.timeout)
        while time.time() < deadline:
            try:
                for btn in self._all_stage_buttons():
                    if "Current Stage" in (btn.text or ""):
                        try:
                            label = btn.find_element(By.CSS_SELECTOR, "p").text.strip().lower()
                        except NoSuchElementException:
                            label = (btn.text or "").splitlines()[0].strip().lower()
                        if label:
                            return label
            except StaleElementReferenceException:
                pass
            time.sleep(0.4)
        raise TimeoutError("Could not read current stage")

    def is_stage_enabled(self, stage):
        """True when the stage button is clickable for the logged-in user."""
        btn = self._stage_button(stage)
        wire_click = btn.get_attribute("wire:click") or ""
        return btn.get_attribute("disabled") is None and f"setStage('{stage}')" in wire_click

    def click_stage(self, stage):
        """Click a stage button.

        Selenium's native `.click()` is not reliably delivered to Livewire's
        Alpine-managed listeners, so we dispatch a JS click instead (verified to
        trigger the wire:action) and retry on stale nodes from re-renders.
        """
        btn = self._stage_button(stage)
        wire_click = btn.get_attribute("wire:click") or ""
        if btn.get_attribute("disabled") is not None or f"setStage('{stage}')" not in wire_click:
            raise PermissionError(f"Stage button '{stage}' is disabled for this account")

        deadline = time.time() + self.timeout
        while True:
            try:
                self.driver.execute_script("arguments[0].click();", btn)
                break
            except StaleElementReferenceException:
                if time.time() > deadline:
                    raise
                btn = self._stage_button(stage)
        self._settle()

    def wait_for_stage(self, stage, timeout=None):
        deadline = time.time() + (timeout or self.timeout)
        while time.time() < deadline:
            try:
                if self.get_current_stage(3) == stage:
                    return
            except TimeoutError:
                pass
            time.sleep(0.4)
        raise TimeoutError(f"Deal did not reach stage '{stage}' (last: {self.get_current_stage(3)})")

    # ------------------------------------------------------------------
    # Permission / isolation checks
    # ------------------------------------------------------------------
    def is_forbidden_page(self):
        """True when the current page is a 403 Forbidden response."""
        try:
            source = self.driver.page_source
        except Exception:
            return False
        has_status = "403" in source
        has_text = any(
            keyword in source
            for keyword in ("Forbidden", "unauthorised", "unauthorized", "not authorised", "not authorized")
        )
        return has_status and has_text

    # ------------------------------------------------------------------
    # Lifecycle
    # ------------------------------------------------------------------
    def quit(self):
        try:
            self.driver.quit()
        except Exception:
            pass
