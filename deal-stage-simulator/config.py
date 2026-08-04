"""
Deal Stage Simulator - Configuration
Central place for URLs, accounts, deal stages and the role/permission matrix.
"""

# ---------------------------------------------------------------------------
# App endpoints
# ---------------------------------------------------------------------------
BASE_URL = "https://thecrm.test"
LOGIN_URL = f"{BASE_URL}/login"
DEALS_URL = f"{BASE_URL}/deals"
DEAL_URL_TEMPLATE = f"{BASE_URL}/deals/{{deal_id}}"

# ---------------------------------------------------------------------------
# Accounts
# ---------------------------------------------------------------------------
ACCOUNT_PASSWORD = "password"

# Sales Team reps - each only manages their OWN deals.
SALES_ACCOUNTS = [
    {"email": "sales@thecrm.com", "name": "Sales"},
    {"email": "sales2@thecrm.com", "name": "Sales 2"},
    {"email": "sales3@thecrm.com", "name": "Sales 3"},
    {"email": "sales4@thecrm.com", "name": "Sales 4"},
]

# Compliance Team - can view/manage every deal and every stage.
COMPLIANCE_ACCOUNT = {"email": "compliance@thecrm.com", "name": "Compliance"}

# ---------------------------------------------------------------------------
# Deal process (stage workflow)
# ---------------------------------------------------------------------------
# The deal pipeline, in order.
STAGE_ORDER = [
    "doc sent",
    "doc signed",
    "compliant",
    "ready for payment",
    "paid",
]

TERMINAL_STAGE = "lost"

# Which stages each team is allowed to MOVE a deal TO (see app/Models/User.php).
SALES_ALLOWED_STAGES = {"doc sent", "doc signed", "compliant", "lost"}
COMPLIANCE_ALLOWED_STAGES = set(STAGE_ORDER) | {TERMINAL_STAGE}

# Allowed forward transitions as enforced by spatie/model-states.
ALLOWED_TRANSITIONS = {
    "doc sent": {"doc signed", "lost"},
    "doc signed": {"compliant", "lost"},
    "compliant": {"ready for payment", "lost"},
    "ready for payment": {"paid", "lost"},
    "paid": set(),
    "lost": set(),
}

# The happy path each sales rep drives their own deal through.
SALES_DRIVE = ["doc signed", "compliant"]

# The path compliance drives deals to after sales hands them off.
COMPLIANCE_DRIVE = ["ready for payment", "paid"]

# Negative permission checks exercised per deal.
#   stage              - the stage the target user must NOT be able to reach
#   expected_disabled  - the UI must render the button disabled (locked)
SALES_BLOCKED_STAGES = ["ready for payment", "paid"]

# ---------------------------------------------------------------------------
# Browser / runtime
# ---------------------------------------------------------------------------
DEFAULT_TIMEOUT = 25          # seconds, element wait timeout
PAGE_LOAD_PAUSE = 2.0         # seconds to let Livewire settle after navigation
LIVEWIRE_SETTLE = 0.6         # seconds after typing / before reading state
