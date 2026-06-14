# The CRM — Processes & Workflows

## Deal Lifecycle

### Creation

1. **Sales user** creates a deal from the Deals page via the ⚡create Livewire component
2. Deal form captures: name, amount, owner, recruitment source, recruitment agency, consultant name
3. On submit:
   - Contact is created via `firstOrCreate` by email (auto-links if the contact already exists)
   - Company is created via `firstOrCreate` by name (auto-links if the company already exists)
   - Deal is created with `user_id` = current user, default `stage`
   - Redirects to the deal detail page
4. `DealObserver::created()` fires:
   - Stamps `stage_updated_at`
   - Sends `DealCreatedNotification` to the deal owner

### Stage Progression

| Step | Stage | Value | Sales Can Move To | Compliance Can Move To |
|------|-------|-------|:-----------------:|:----------------------:|
| 1 | **Doc Sent** | `doc sent` | ✅ | ✅ |
| 2 | **Doc Signed** | `doc signed` | ✅ | ✅ |
| 3 | **Compliant** | `compliant` | ✅ | ✅ |
| 4 | **Ready for Payment** | `ready for payment` | ❌ | ✅ |
| 5 | **Paid** | `paid` | ❌ | ✅ |

**Stage Enforcement:**
- Sales Team can only move deals up to and including `Compliant`
- Once a deal reaches `Ready for Payment` or `Paid`, Sales can no longer edit it
- Compliance Team can move deals to any stage at any time
- All stage changes are logged to the `deal_histories` table with reason and metadata
- `DealObserver::updated()` detects stage changes, stamps `stage_updated_at`, and notifies Compliance + deal owner

### Stale Deal Detection

- Scheduled command `deals:check-stale-stages` runs hourly
- Finds deals stuck in `Doc Sent` for more than 24 hours (based on `stage_updated_at`)
- Sends `DealStageStaleNotification` to deal owner
- Deduplication: skips if the owner already has an unread notification within 24 hours

## Team Permissions

### Sales Team

- See only deals they own (`user_id` = current user)
- Create, edit, and delete their own deals
- Move deals through stages: Doc Sent → Doc Signed → Compliant
- Cannot move deals to Ready for Payment or Paid
- Cannot edit deals that have reached Ready for Payment or Paid
- Cannot perform batch operations (owner change, stage change, delete)

### Compliance Team

- See all deals regardless of owner
- Move deals to any stage at any time
- Edit any deal regardless of stage
- Perform batch operations: update owner, update stage, delete records
- Manage users, teams, and GDPR settings

### Users Without a Team

- Default to full visibility (same as Compliance)
- No stage restrictions applied

## Document Signing (Signable Module)

The application integrates with **Signable** via `Modules/Signable` for electronic document signing.

### Workflow

1. From a deal's **Overview** tab, the Signable envelope wizard guides the user through:
   - Selecting a template or uploading a document
   - Adding signing parties (contacts from the deal)
   - Configuring signing order and fields
   - Sending the envelope
2. `SendEnvelopeController` calls the Signable API via `SignableClient` (Guzzle-based)
3. `DealSignableEnvelope` model tracks: envelope ID, status (`sent`, `signed`, `draft`, `cancelled`), parties
4. Webhook endpoint (`SignableWebhookController`) receives real-time status updates from Signable
5. When documents are signed, the deal stage can be moved to Doc Signed

### API Controllers

The Signable module exposes 7 API controllers: Envelope, Template, Contact, User, Settings, Branding, and Webhook.

### Configuration

```env
SIGNABLE_API_SERVER=
SIGNABLE_API_KEY=
SIGNABLE_API_SECRET=
SIGNABLE_API_TIMEOUT=30
SIGNABLE_API_USER_ID=
```

## Accounting Integration (MyDigitalAccounts Module)

The CRM connects to **MyDigitalAccounts** (MDA v1 API) for accounting data sync.

### Architecture

- `MyDigitalAccountsClient` — rate-limited Guzzle API client
- `FetchCompaniesAction` — fetches companies from MDA
- **DTOs:** `CompanyData`, `EmployeeData`, `InvoiceData`, `PaginatedData` (strict type safety)

### Deal-Level Tracking

Each deal tracks MDA-related fields:
- `mda_setup` — which internal company is used (enum: `umbrella company`, `churchill knight umbrella`, `churchill knight associates`)
- `mda_reference_number` — MDA reference
- `date_set_up` — when the MDA account was created
- `remittance_received` — yes/no
- `date_logged` — when the payment was logged

## Email System

### Template Designer

- Built at `/designer` with create, edit, and list views
- Supports HTML builder mode and legacy plain text mode
- Placeholder parsing: `{{contact_name}}`, `{{deal_amount}}`, `{{company_name}}`, etc. via `EmailTemplateParser`
- Attachments supported via `EmailTemplateAttachment` model

### Sending Flow

1. `DealEmailService::send()` parses the template and merges attachments
2. Creates a `DealEmailLog` with status `pending`
3. `SendDealEmailJob` (queueable, 3 retries) sends the email via `DealEmailMailable`
4. Updates log to `sent` or `failed` with error message
5. Every email sent is logged for audit purposes

### Notifications

| Notification | Channels | Trigger |
|-------------|----------|---------|
| `DealCreatedNotification` | Mail + Database | New deal created |
| `DealStageMovedNotification` | Database only | Deal stage changed |
| `DealStageStaleNotification` | Mail + Database | Deal stuck in Doc Sent >24h |
| `DealReadyForPaymentNotification` | Mail only | Deal reaches payment stage |
| `DealCommentedNotification` | Mail + Database | Comment added to deal activity |
| `DealActivityNotification` | Mail + Database | New activity logged on a deal |

## GDPR Compliance

### User Data Export

1. User requests export at `/gdpr/export`
2. `GdprExportService` generates a package (JSON/ZIP) of all their data
3. Download link provided via secure token at `/gdpr/download/{token}`
4. Export includes: profile, deals, contacts, activity logs, notifications

### Data Retention & Anonymization

- `GdprSetting` stores configurable retention periods per data type
- `AnonymizeExpiredData` command runs daily (scheduled in `routes/console.php`)
- Anonymizes PII (name, email, phone, address, NI number, bank details) on expired records
- Records marked with `marked_for_deletion_on` timestamp on both `contacts` and `users` tables

### Admin GDPR Dashboard

Accessible at `/admin/gdpr` (requires `manage-gdpr` permission):
- Configure retention settings
- Run anonymization job manually
- Import/export settings (for migrating between environments)

## Media Management

- **Spatie MediaLibrary** handles file uploads on Deal models
- Two collections:
  - `compliance_documents` — compliance files (right to work, proof of address, etc.)
  - `contract_documents` — contract and agreement files
- File constraints: PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG — max 20MB each
- Uploaded via Livewire `WithFileUploads` trait with loading states
- Each upload is logged to the deal's activity history
- Documents can be deleted individually with confirmation

## Import/Export

### Imports

- **Companies** — POST `/import-companies` accepts Excel/CSV via `CompanyImport`
- **Contacts** — CSV/Excel upload via `ContactsImport` with heading mapping and validation

### Exports

- **Deals** — GET `/deals/export` via `DealsExport` (Maatwebsite Excel)
- 32 columns: deal details, contact info, company info, compliance data
- Respects team-based visibility (Sales only see their own deals)
- Supports filtering by: name, owner, contact, company, stage, amount range, date range

## Real-Time Architecture

- **Livewire polling:** Deals table polls every 5 seconds, deal detail every 10 seconds
- **Broadcasting:** `echo:deals,DealCreated` event pushes new deals to connected clients via Alpine.js listener
- **Laravel Echo + Pusher:** Configured for production WebSocket support
- **Queue:** Database driver, worker runs every minute via scheduler (`queue:work --stop-when-empty`)

## Database Strategy

- **Development:** SQLite (`database/database.sqlite`) for zero-config local setup
- **Production:** MySQL with strategic indexes
- All queries use Eloquent builder for database portability — no MySQL-specific raw SQL
- Date functions (YEARWEEK, DATE_SUB) handled at PHP level via Carbon for cross-database compatibility

### Indexes

| Index | Columns | Purpose |
|-------|---------|---------|
| `deals_stage_user_updated_idx` | stage, user_id, updated_at | Kanban column queries |
| `deals_updated_at_idx` | updated_at | Sorting |
| `deals_stage_updated_at_idx` | stage_updated_at | Stale deal detection |
| `deals_amount_idx` | amount | Financial filtering |
| `deals_created_stage_idx` | created_at, stage | Date + stage filtering |

## Audit Trail

Every change to a deal is recorded in `deal_histories` via the `LogsDealHistory` trait:

| Action | Triggered By | Details Logged |
|--------|-------------|----------------|
| `created` | Deal creation | Name, amount, stage |
| `stage_moved` | Stage change | Old stage, new stage, reason |
| `details_updated` | Field edits | Field name, old value, new value |
| `association_updated` | Contact/company changes | Entity type, action, entity details |
| `owner_changed` | Deal owner transfer | Old owner, new owner |

The `DealHistory` model provides scopes: `ofAction()`, `stageChanges()`, `recent()`.
History is displayed in the deal detail view via the ⚡history-timeline component.

## Scheduled Commands

| Command | Frequency | Purpose |
|---------|-----------|---------|
| `queue:work --stop-when-empty` | Every minute | Process queued jobs |
| `deals:check-stale-stages` | Hourly | Alert on deals stuck in Doc Sent >24h |
| `gdpr:anonymize-expired` | Daily | Anonymize PII past retention period |
