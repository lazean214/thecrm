## Getting Started

### Logging In

1. Navigate to `https://thecrm.test/login`
2. Enter your email and password
3. Click **Sign In**
4. If 2FA is enabled, enter the code from your authenticator app

### Your Dashboard

After logging in, you'll see the **Deal Pipeline Dashboard** showing:

- **Pipeline stats** — total pipeline value, total active deals, average margin
- **Stage distribution** — deals grouped by pipeline stage with counts and values
- **Weekly summary** — last 12 weeks of paid deals and new doc-sent deals
- **TSV reports** — exportable breakdowns by company, contact, deal owner, and individual deal

Use the sidebar to navigate between sections.

---

## Deals

The deal pipeline has 6 stages that a deal moves through:

```
Doc Sent → Doc Signed → Compliant → Ready for Payment → Paid
                                                          ↘ Lost
```

### Viewing Deals

- **Table view** — paginated with 26 configurable columns. Click any deal to open it. Use the column chooser to show/hide columns.
- **Kanban view** — drag-and-drop cards between pipeline stages with real-time sync, auto-scroll, and permission-aware locked columns.

Use the toggle at the top to switch between views.

### Filtering Deals

The filter panel supports:

- Deal name search (live)
- Deal owner, contact name, company name (autocomplete)
- Stage (dropdown)
- Amount range (min/max)
- Created date range

Filters persist across page loads. Click **View All Time** to clear the date default.

### Creating a Deal

1. Click **Deals → Add Deal**
2. Fill in the required fields:
   - **Deal name** (required)
   - **Email** (required)
   - **First name** (required)
   - **Consultant name** (required)
3. Optionally set: amount, stage, recruitment source, agency deal value, margin agreed, phone, last name
4. Click **Save**

### Editing a Deal

Open the deal — the entire view is an inline edit form. Update any fields in the left sidebar (Deal Details, Worker Details, MDA) or the Compliance section, then click **Save Changes** at the bottom. Click **Disregard** to discard unsaved changes.

### Deal View Layout

The deal view has three sidebar sections and four main tabs:

**Left Sidebar:**

| Section | Fields |
|---------|--------|
| **Deal Details** | Name, owner, amount (TSV), recruitment source, recruitment agency, agency deal value, margin agreed, created/modified dates |
| **Worker Details** | First name, last name, email, phone, DOB, gender, marital status, full address, NI number, bank, account number, sort code |
| **MDA** | MDA setup (internal company), MDA reference number, date set up, remittance received (Yes/No), date logged |

**Main Tabs:**

| Tab | Content |
|-----|---------|
| **Overview** | Signable envelope wizard for sending documents for signature |
| **Activities** | Activity feed with tasks and updates |
| **Welcome Email** | Send worker welcome emails |
| **History** | Visual timeline of all deal changes (who did what and when) |

**Below Tabs:**

- **Compliance section** — date sent, date signed, who signed, signed document, starter checklist date, starter form (A/B/C), tax code (1257L / 1257L1 / BR), contract received date, right to work document type, proof of address, right to work, compliance document uploads, contract document uploads
- **Attached Documents** — uploaded compliance and contract documents with delete capability

### Stage Navigator

A visual step bar at the top of the deal view shows all 6 stages. Click a stage to move the deal there (if permitted). Locked stages show a lock icon; completed stages show a checkmark.

### Moving a Deal Through the Pipeline

- In Kanban view: drag the card to the next stage column
- In the deal view: use the **Stage Navigator** step bar or the stage dropdown

**Stage permissions by team:**

| Team | Allowed Stages |
|------|---------------|
| Sales Team | Doc Sent, Doc Signed, Compliant, Lost |
| Compliance Team | All 6 stages |
| Admin / No team | All 6 stages |

Sales Team can move deals to any of their 4 allowed stages (forward or backward). Compliance Team and Admin can move deals to any stage.

### Batch Operations

Select multiple deals in table view to access batch operations:

| Operation | Who Can Do It |
|-----------|--------------|
| Update Owner | Compliance Team only |
| Update Stage | Compliance Team; Sales Team (own deals only) |
| Delete Records | Compliance Team only |

### Deal Export

Export filtered deals to Excel from the table view. The export includes 30 columns covering deal fields, contact details (including NI/bank info), and company information. All active filters are applied to the export.

### AI Action Prompts

The deal view shows AI-powered suggested next-step actions based on the current stage:

| Stage | Typical Suggestions |
|-------|-------------------|
| Doc Sent | Follow up for signatures, verify correct documents |
| Doc Signed | Request missing compliance docs, upload signed contract |
| Compliant | Confirm docs complete, set up MDA |
| Ready for Payment | Process payment, confirm remittance details |
| Paid | Send payment confirmation, follow up for satisfaction |
| Lost | Document reasons, consider future outreach |

### Stale Deal Notifications

Deals stuck in **Doc Sent** for more than 24 hours trigger an automatic notification to the deal owner via database notification and email.

### Deleting a Deal

Open the deal → click **Delete** at the bottom → confirm. Only Admin users can delete deals. Deleted deals cannot be recovered.

---

## Contacts

Contacts are people associated with your deals and companies.

### Contact Fields

| Category | Fields |
|----------|--------|
| **Basic** | First name, last name, email, phone |
| **Address** | Street address, city, state, postal code, country |
| **Personal** | Date of birth, gender, marital status |
| **Financial** | NI number, bank, account number, sort code |

### Adding a Contact

1. Go to **Contacts → Add Contact**
2. Enter the contact details
3. Click **Save**

### Viewing a Contact

Open a contact to see their full details along with associated deals and companies.

### Importing Contacts

1. Go to **Contacts → Import**
2. Upload a CSV or XLSX file
3. Map the columns to contact fields
4. Click **Import**

### Deleting a Contact

Open the contact index → select the contact → click **Delete**.

---

## Companies

### Adding a Company

1. Go to **Companies → Add Company**
2. Enter company name, email, domain, phone
3. Click **Save**

### Viewing a Company

Open a company to see its details along with associated contacts, deals, and email logs.

### Setting a Primary Contact

Each company can have one primary contact. Open the company → find the contact → click **Set as Primary**.

### Importing Companies

1. Go to **Companies → Import**
2. Upload a CSV or XLSX file
3. Map the columns
4. Click **Import**

### Editing a Company

Open the company → update any fields inline → save.

### Deleting a Company

Open the company index → select the company → click **Delete**.

---

## Document Signing (Signable)

Send contracts and documents for electronic signature. Signable can be used from within a deal or as a standalone feature.

### Sending from a Deal

1. Open a deal
2. Go to the **Overview** tab
3. Use the envelope wizard to create and send a document

### Standalone Envelope Wizard

1. Go to **Signable → Send Envelope** (`/envelopes/send`)
2. Follow the 4-step wizard:
   - **Step 1 — Envelope Details** — title, user ID, redirect URL, auto-expire, auto-remind, send-all-at-once, require OTP
   - **Step 2 — Document** — choose a template, multiple templates, or upload/link a document
   - **Step 3 — Parties** — add signers with name, email, role, mobile, message
   - **Step 4 — Review** — confirm details and send

### Envelope Desk

The Envelope Desk (`/envelopes`) provides a global overview of all envelopes across all deals:

- **Search and filter** by title, email, fingerprint, status, and date range
- **Status filter** — sent, signed, draft, cancelled, expired, rejected, processing, failed, verify
- **Download** individual signed PDFs
- **Batch download** — select multiple envelopes and download as a ZIP
- **Export to Excel** — export filtered envelopes to CSV
- **Pagination** with configurable row count (10/25/50)

### Tracking Signature Status

Envelope statuses update automatically via webhooks:

| Status | Meaning |
|--------|---------|
| **Draft** | Not yet sent |
| **Sent** | Awaiting signatures |
| **Signed** | All parties have signed |
| **Cancelled** | Sender cancelled the request |
| **Expired** | Signature deadline passed |
| **Rejected** | A party rejected the envelope |
| **Processing** | Being processed by Signable |
| **Failed** | An error occurred |

### Downloading Signed Documents

- From a deal: open the deal → **Overview** tab → download from the envelope list
- From the Envelope Desk: click the download icon next to any envelope
- Batch: select multiple envelopes → **Batch Download Selected** → download as ZIP

---

## Remittances

Remittances track worker timesheets, payments, and margins.

### Remittance Table

An editable spreadsheet-like interface for entering and managing remittance data. When you select a contact, it auto-fills from their primary deal (hours, rate, margin, amount, compliance status).

### Remittance Report

A summary dashboard showing:

- **Stats cards** — active/inactive billers, total billers, total TSV, total hours, companies
- **Charts** — billers by deal owner (bar chart), workers by company (doughnut chart)
- **Summary table** — per-worker breakdown with week, agency, CID, TSV
- **Timesheet breakdown** — expandable per-worker detail with week, agency, shift, hours, rate, TSV, margin, status
- **Deal owner breakdown** — per-owner stats with active billers, total TSV, average TSV per biller
- **Company breakdown** — per-company stats with workers, total TSV, average TSV per worker

### Filters

Filter by period (All Time / Last 30 Days / Last 90 Days), date range, worker name, agency, company, CID, and deal owner.

### Export

Export remittance data via the summary, breakdown, or unified report export buttons.

---

## Audit History

Every change to a deal is logged for compliance.

### Viewing the History

1. Open a deal
2. Go to the **History** tab
3. See a visual timeline of all changes

### What Gets Logged

| Action | Details |
|--------|---------|
| **Created** | Deal creation with name, amount, stage |
| **Stage moved** | Old stage → new stage with reason (Sales/Compliance/System) |
| **Details updated** | Field-by-field changes with old and new values |
| **Association updated** | Contact/company link changes |
| **Owner changed** | Previous owner → new owner |

Visible to: Sales (own deals), Compliance and Admin (all deals).

---

## GDPR & Data Privacy

### Requesting Your Data

1. Go to **GDPR → Request My Data** (`/gdpr/export`)
2. Click **Request Data Export**
3. Your export will be processed (JSON format covering profile, contacts, companies, deal history, communication logs)
4. Download link provided (valid for 7 days)

### Admin: Data Retention Settings

Admin users can configure retention at **Admin → GDPR** (`/admin/gdpr`):

- Set retention periods per entity type (contacts, email logs, activity logs, deal histories)
- Choose action: anonymise, delete permanently, or notify admin only
- Run retention manually with **Run Retention Now**
- View recent export requests and their status

### Admin: Data Anonymisation

When retention runs, personal data is anonymised:
- Names replaced with "GDPR Deleted"
- Emails replaced with `deleted_{id}@gdpr.local`
- Old records are permanently removed

---

## Admin Features

### User Management

Admin users can manage users at `/users`:

- List all users with name, email, roles, and teams
- Create new users
- Edit existing users
- Assign roles and teams

### Roles & Permissions

Manage roles at `/roles` and permissions at `/permissions`. The system uses Spatie Laravel Permission for role-based access control.

### Team Management

Manage teams at `/teams`:

- Create and edit teams
- Assign users to teams
- Teams control deal visibility and stage permissions:
  - **Sales Team** — can only see their own deals; limited stage movement
  - **Compliance Team** — can see all deals; full stage movement

---

## User Profile & Settings

### Changing Your Password

1. Click your avatar (top-right) → **Profile**
2. Go to **Security** tab
3. Enter current password and new password
4. Click **Save**

### Setting Up Two-Factor Authentication (2FA)

1. Profile → **Security** tab
2. Click **Enable 2FA**
3. Scan the QR code with your authenticator app (Google Authenticator, Authy, etc.)
4. Enter the code to verify
5. Save your recovery codes somewhere safe

### Setting Up Passkeys (Passwordless Login)

1. Profile → **Security** tab
2. Click **Add Passkey**
3. Use your device biometrics (fingerprint, face) or PIN
4. Name the passkey and save

You can now log in using your passkey instead of a password.

### Appearance

Go to **Settings → Appearance** to switch between Light, Dark, or System theme.

### Notifications

Go to **Settings → Notifications** to toggle which events trigger email notifications.

### Fiscal Year (Admin)

Go to **Settings → Fiscal Year** to configure the UK fiscal year period:
- Set start month/day and end month/day
- Preview the current fiscal year period
- View auto-generated week number mapping for the fiscal year

### Data Management (Admin)

Go to **Settings → Data Management** to view record counts and purge data:

| Action | What It Removes |
|--------|----------------|
| **Purge Deals** | All deals + related envelopes, contacts, companies |
| **Purge Contacts** | All contacts + deal associations |
| **Purge Companies** | All companies + deal associations |

**Warning:** Purging is irreversible.

---

## Notifications

- **In-app** — toast notifications appear at the top-right when things happen
- **Email** — you'll receive emails for deal stage changes and signing requests
- **Stale deal alerts** — automatic notification when a deal is stuck in Doc Sent for 24+ hours

### Managing Notification Preferences

Profile → **Notifications** → toggle which events you want email notifications for.

---

## Quick Reference

| Task | How to do it |
|------|-------------|
| Create a deal | Deals → Add Deal |
| Move deal to next stage | Open deal → click stage in Stage Navigator (or drag in Kanban) |
| Filter deals | Deals → use filter panel (name, owner, stage, amount, date) |
| Export deals to Excel | Deals → table view → Export |
| Batch update deals | Deals → table view → select deals → choose batch action |
| Add a contact | Contacts → Add Contact |
| Import contacts | Contacts → Import → upload CSV/XLSX |
| Add a company | Companies → Add Company |
| Import companies | Companies → Import → upload CSV/XLSX |
| Send document for signing | Open deal → Overview tab → use wizard, OR Signable → Send Envelope |
| View all envelopes | Signable → Envelope Desk |
| Download signed documents | Envelope Desk → download icon, or batch download as ZIP |
| View remittance report | Remittances → Report |
| View deal history | Open deal → History tab |
| Manage users | Admin → Users |
| Manage teams | Admin → Teams |
| Configure fiscal year | Settings → Fiscal Year |
| Change password | Profile → Security |
| Enable 2FA | Profile → Security → Enable 2FA |
| Switch theme | Settings → Appearance |
| Request data export | GDPR → Request My Data |
| Log out | Click avatar → Sign Out |

---

## Need Help?

If you encounter issues or need access to a feature you don't see, contact your system administrator.

---

_CRM User Guide v2.1_
