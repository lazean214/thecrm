# Deal Stage Simulator

A Python/Selenium tool that **launches simultaneous deal-stage management**
across multiple CRM accounts and verifies the deal process + role permissions.

It opens one real Chrome browser per account, all at the same time:

| Account            | Role        | What it does in the simulation                     |
|--------------------|-------------|----------------------------------------------------|
| `sales@thecrm.com`   | Sales rep 1 | creates deals, drives `doc sent → doc signed → compliant` |
| `sales2@thecrm.com`  | Sales rep 2 | same, own deals                                    |
| `sales3@thecrm.com`  | Sales rep 3 | same, own deals                                    |
| `sales4@thecrm.com`  | Sales rep 4 | same, own deals                                    |
| `compliance@thecrm.com` | Compliance | concurrently drives every deal `compliant → ready for payment → paid` |

## The deal process under test

Pipeline (from `app/Enums/DealStage.php`):

```
doc sent → doc signed → compliant → ready for payment → paid
            └─────────────── lost (terminal) ────────────┘
```

Permissions (from `app/Models/User.php`, `app/Policies/DealPolicy.php` and the
Livewire `deals.view`/`deals.table` components):

- **Sales Team** – can only view/update **their own** deals, and can only move
  deals to `doc sent`, `doc signed`, `compliant` or `lost`.
  `ready for payment` and `paid` are shown as locked (`🔒`, disabled) buttons.
- **Compliance Team** – can view/update **every** deal and move to **all** stages.
- **Admin** – full access (not used by this simulation).

## Installation

```bash
cd deal-stage-simulator
python -m venv .venv
.venv\Scripts\pip install -r requirements.txt
```

Requires Google Chrome (the driver is downloaded automatically).

## Usage

```bash
# Full run: all 4 sales reps + compliance, 2 deals per rep
python run.py --deals-per-rep 2

# Headless quick smoke test (1 rep, 1 deal)
python run.py --sales sales@thecrm.com --deals-per-rep 1 --headless

# Custom rep set
python run.py --sales sales@thecrm.com,sales2@thecrm.com --deals-per-rep 3

# Slow the pacing down between stage transitions (seconds)
python run.py --interval 1
```

### Options

| Option            | Default                | Description                                   |
|-------------------|------------------------|-----------------------------------------------|
| `--sales`         | all sales accounts     | comma-separated sales emails                  |
| `--compliance`    | `compliance@thecrm.com`| compliance account email                      |
| `--deals-per-rep` | `2`                    | deals created by each sales rep               |
| `--headless`      | off                    | run Chrome without a window                   |
| `--interval`      | `0`                    | seconds paused between transitions            |
| `--timeout`       | `25`                   | per-step element wait timeout (seconds)       |
| `--output`        | `reports`              | folder for JSON/CSV reports                   |

## What it verifies

Each run records PASS/FAIL per scenario:

- `create_deal` — rep creates a deal and lands on `/deals/{id}`.
- `initial_stage` — new deals start at `doc sent`.
- `move:doc signed` / `move:compliant` — sales rep moves their own deal forward.
- `blocked:ready for payment` / `blocked:paid` — stage buttons are locked for sales.
- `isolation_view_other` — a rep gets **403** when opening another rep's deal.
- `can_view` — compliance can open any rep's deal.
- `compliance_move:ready for payment` / `compliance_move:paid` — compliance finishes the pipeline.

A human-readable summary is printed to the console and the full result set is
written to `reports/report_<timestamp>.json` and `.csv`.

## Notes

- Each account uses its own browser session and all sessions begin their stage
  work simultaneously (a start gate). All browsers share one lightweight
  in-memory registry so compliance knows which deals sales created.
- Every deal created is unique per run (`sim.<run-token>.<rep>.<n>@example.test`),
  so repeated runs never collide.
- `crm-automation/` (the bulk CSV importer) is intentionally left untouched.
