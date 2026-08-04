"""
Deal Stage Simulator - CLI entry point.

Launches concurrent browser sessions (one per sales rep + one compliance) that
drive deals through the CRM pipeline at the same time and verify the role-based
permission rules.

Usage examples:
    # Full run: all sales reps + compliance, 2 deals per rep (headed browsers)
    python run.py --deals-per-rep 2

    # Headless, one rep, one deal (quick smoke test)
    python run.py --sales sales@thecrm.com --deals-per-rep 1 --headless

    # Custom reps
    python run.py --sales sales@thecrm.com,sales2@thecrm.com --deals-per-rep 3
"""

import argparse
import csv
import json
import os
import sys
from datetime import datetime

import config
from simulator import print_report, run_simulation


def parse_sales(value):
    if not value:
        return list(config.SALES_ACCOUNTS)
    emails = [e.strip() for e in value.split(",") if e.strip()]
    known = {a["email"]: a for a in config.SALES_ACCOUNTS}
    return [known[e] for e in emails if e in known]


def main():
    parser = argparse.ArgumentParser(
        description="Concurrent deal-stage simulation against the CRM.",
        formatter_class=argparse.RawDescriptionHelpFormatter,
    )
    parser.add_argument(
        "--sales",
        default=None,
        help="Comma-separated sales emails (default: all sales accounts in config.py)",
    )
    parser.add_argument(
        "--compliance",
        default=config.COMPLIANCE_ACCOUNT["email"],
        help="Compliance account email",
    )
    parser.add_argument(
        "--deals-per-rep",
        type=int,
        default=2,
        help="Number of deals each sales rep creates (default: 2)",
    )
    parser.add_argument(
        "--headless",
        action="store_true",
        help="Run Chrome without a visible window",
    )
    parser.add_argument(
        "--interval",
        type=float,
        default=0.0,
        help="Extra seconds to pause between stage transitions (default: 0)",
    )
    parser.add_argument(
        "--timeout",
        type=int,
        default=config.DEFAULT_TIMEOUT,
        help=f"Per-step timeout in seconds (default: {config.DEFAULT_TIMEOUT})",
    )
    parser.add_argument(
        "--output",
        default="reports",
        help="Directory to write report files into (default: reports)",
    )
    args = parser.parse_args()

    sales_accounts = parse_sales(args.sales)
    if not sales_accounts:
        print("No valid sales accounts configured.")
        sys.exit(1)

    compliance_account = next(
        (a for a in [config.COMPLIANCE_ACCOUNT] if a["email"] == args.compliance),
        {"email": args.compliance, "name": args.compliance},
    )

    print("=" * 78)
    print("DEAL STAGE SIMULATOR")
    print("=" * 78)
    print(f"Base URL   : {config.BASE_URL}")
    print(f"Sales reps : {len(sales_accounts)} -> {[a['email'] for a in sales_accounts]}")
    print(f"Compliance : {compliance_account['email']}")
    print(f"Deals/rep  : {args.deals_per_rep}")
    print(f"Headless   : {args.headless}")
    print("=" * 78)

    results = run_simulation(
        sales_accounts=sales_accounts,
        compliance_account=compliance_account,
        deals_per_rep=args.deals_per_rep,
        headless=args.headless,
        interval=args.interval,
        timeout=args.timeout,
    )

    print_report(results)

    os.makedirs(args.output, exist_ok=True)
    stamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    json_path = os.path.join(args.output, f"report_{stamp}.json")
    csv_path = os.path.join(args.output, f"report_{stamp}.csv")

    with open(json_path, "w", encoding="utf-8") as fh:
        json.dump(results, fh, indent=2)

    if results:
        with open(csv_path, "w", newline="", encoding="utf-8") as fh:
            writer = csv.DictWriter(fh, fieldnames=list(results[0].keys()))
            writer.writeheader()
            writer.writerows(results)

    print(f"\nReports written to:\n  {json_path}\n  {csv_path}")


if __name__ == "__main__":
    main()
