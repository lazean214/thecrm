# The CRM

A modern, high-performance CRM built with the latest Laravel ecosystem. Designed for managing deals, contacts, companies, and compliance workflows with electronic document signing, GDPR compliance, and accounting integration.

## 🚀 Key Features

- **Deal Pipeline** — Kanban board with drag-and-drop stage management or table view with pagination, column chooser, and batch operations
- **5-Stage Workflow** — Doc Sent → Doc Signed → Compliant → Ready for Payment → Paid, with team-based stage restrictions
- **Role-Based Access Control** — Sales teams see and edit only their own deals; Compliance teams have full visibility and control over all stages
- **Advanced CRM Core** — Manage Contacts and Companies with many-to-many relationships, primary entity designations, and bulk import/export
- **Electronic Document Signing** — Signable integration for creating, sending, and tracking signing envelopes directly from deals
- **Email Template Designer** — Built-in drag-and-drop email designer with placeholder parsing, attachment support, and automated deal notifications
- **Accounting Integration** — MyDigitalAccounts (MDA) module for syncing company data and managing umbrella company references
- **GDPR Compliance** — Data export requests, configurable retention policies, automated anonymization, and admin dashboard
- **Complete Audit Trail** — Every deal change (stage moves, field edits, owner transfers, association updates, document uploads) is logged with structured metadata
- **Real-Time Updates** — Livewire polling with WebSocket broadcasting for live deal creation
- **Modern Authentication** — Passkeys (WebAuthn), two-factor authentication (TOTP), email verification, and password reset via Laravel Fortify
- **Performance Monitoring** — Laravel Pulse dashboard for tracking application health

## 🛠 Tech Stack

| Layer | Technology |
|-------|-----------|
| **Framework** | Laravel 13 |
| **PHP** | 8.4 |
| **Frontend** | Livewire 4 + Flux UI 2 |
| **CSS** | Tailwind CSS 4 (zinc/slate palette, dark mode) |
| **JS** | Alpine.js 3 (collapse, intersect plugins) |
| **Database** | SQLite (dev) / MySQL (production) |
| **Auth** | Laravel Fortify 1 (passkeys, 2FA) |
| **API Tokens** | Laravel Sanctum 4 |
| **Queues** | Database driver (scheduled worker) |
| **Monitoring** | Laravel Pulse 1 |
| **Media** | Spatie MediaLibrary |
| **Excel/CSV** | Maatwebsite Excel 3 |
| **Real-Time** | Laravel Echo 2 + Pusher |
| **Testing** | Pest 4 |
| **Modular** | nwidart/laravel-modules |

## 📦 Installation

### Prerequisites

- PHP 8.4+
- Composer 2.x
- Node.js 18+
- SQLite (for local development) or MySQL (for production)

### Setup

1. **Clone the repository:**
   ```bash
   git clone https://github.com/lazean214/thecrm.git
   cd thecrm
   ```

2. **Install dependencies and configure:**
   ```bash
   composer run setup
   ```
   This installs Composer and NPM dependencies, creates `.env`, generates the app key, runs migrations, and builds assets.

3. **Configure your environment:**
   Edit `.env` for database, mail, and external service keys. For local development the defaults work with SQLite:
   ```env
   DB_CONNECTION=sqlite
   SESSION_SECURE_COOKIE=false   # Set to true for production HTTPS
   ```

4. **Seed sample data (optional):**
   ```bash
   php artisan db:seed
   ```
   Default admin user: `admin@thecrm.com` / `password`

5. **Start the development environment:**
   ```bash
   composer run dev
   ```

## 💻 Development

```bash
# Start dev server + queue + Vite
composer run dev

# Run tests
composer test

# Format code
vendor/bin/pint

# Run a single test file
php artisan test --compact tests/Feature/Api/DealApiTest.php
```

## 📂 Project Structure

```
app/
├── Console/Commands/        # Artisan commands (GDPR, deal staging)
├── Enums/                   # DealStage, InternalCompany
├── Exports/                 # Excel/CSV exports
├── Http/                    # Controllers, requests, resources
├── Imports/                 # Company and contact CSV imports
├── Jobs/                    # SendDealEmailJob
├── Mail/                    # DealEmailMailable
├── Models/                  # Core models (Deal, Contact, Company, User, etc.)
├── Notifications/           # Deal lifecycle notifications
├── Observers/               # DealObserver (history logging)
├── Services/                # Email, GDPR export/retention
└── Traits/                  # LogsDealHistory, LogsContactHistory

Modules/
├── Signable/                # Electronic document signing integration
│   ├── Http/Controllers/Api/  # 7 API controllers
│   ├── Services/Signable/     # SignableClient (Guzzle)
│   ├── Enums/                 # SignableStatus
│   └── Livewire/              # EnvelopeForm
└── MyDigitalAccounts/       # Accounting software integration (MDA v1 API)
    ├── Domain/                  # MyDigitalAccountsClient
    ├── Actions/                 # FetchCompaniesAction
    └── Data/                    # DTOs (Company, Employee, Invoice)

resources/views/
├── components/deals/          # Deal pipeline (kanban, table, view, filters)
│   └── partials/              # Kanban cards, stage navigator, history timeline
├── components/contacts/       # Contact CRUD
├── components/companies/      # Company CRUD
├── components/dashboard/      # Pipeline report
├── components/activities/     # Email designer, task comments
├── components/signable/       # Envelope creation
├── components/teams/          # Team management
└── components/users/          # User management

database/migrations/           # 37 migrations
tests/                         # Pest test suite (30+ tests)
```

## 🔄 Deal Pipeline Stages

| Stage | Value | Sales Can Move To | Compliance Can Move To |
|-------|-------|:-----------------:|:----------------------:|
| Doc Sent | `doc sent` | ✅ | ✅ |
| Doc Signed | `doc signed` | ✅ | ✅ |
| Compliant | `compliant` | ✅ | ✅ |
| Ready for Payment | `ready for payment` | ❌ (Compliance only) | ✅ |
| Paid | `paid` | ❌ (Compliance only) | ✅ |

## 📡 External Services

| Service | Purpose | Environment Variables |
|---------|---------|----------------------|
| **Signable** | Electronic document signing | `SIGNABLE_API_KEY`, `SIGNABLE_API_SERVER`, `SIGNABLE_API_SECRET` |
| **MyDigitalAccounts** | Accounting sync | Module config (`config/mydigitalaccounts.php`) |
| **Mail** | Email delivery | `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT` |
| **Pusher** | Real-time broadcasting | `PUSHER_APP_*` keys |

## 🧪 Testing

```bash
# Full suite
composer test

# Specific test
php artisan test --compact tests/Feature/Livewire/DealsTableTest.php

# Filter by name
php artisan test --compact --filter=DealApi
```

Test coverage includes API CRUD, Livewire component behavior, stage restriction enforcement, email template rendering, authentication flows, and settings management.

## 📜 License

The CRM is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).
