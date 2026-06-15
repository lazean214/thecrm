# CRM User Guide

A comprehensive guide to using the CRM system for managing deals, workers, and compliance.

---

## Table of Contents

1. [Getting Started](#getting-started)
2. [Dashboard](#dashboard)
3. [Deals Management](#deals-management)
4. [Pipeline (Kanban Board)](#pipeline-kanban-board)
5. [Deal Details](#deal-details)
6. [Contacts](#contacts)
7. [Companies](#companies)
8. [Users & Teams](#users--teams)
9. [Envelopes & Signatures](#envelopes--signatures)
10. [Email Templates](#email-templates)
11. [GDPR Compliance](#gdpr-compliance)
12. [Notifications](#notifications)

---

## Getting Started

### Accessing the CRM

Access the CRM by navigating to your application URL. You'll see the main navigation on the left sidebar.

### Navigation

- **Dashboard** - Overview of your pipeline and recent activity
- **Deals** - Manage your deal pipeline
- **Contacts** - Manage worker/contractor contacts
- **Companies** - Manage recruitment agencies and umbrella companies
- **Envelopes** - Document signing workflows
- **Email Designer** - Create email templates
- **Settings** - User preferences

---

## Dashboard

The dashboard provides a high-level view of your sales pipeline:

- **Pipeline Summary** - Total deals and value at each stage
- **Recent Activity** - Latest deal movements
- **Quick Actions** - Create new deals, contacts, or companies

---

## Deals Management

### Creating a Deal

1. Navigate to **Deals**
2. Click the **+ New Deal** button
3. Fill in the required information:
   - **Deal Name** - Worker or contractor name
   - **Owner** - Sales team member responsible
   - **Company** - Recruitment agency or umbrella company
   - **Amount** - Deal value

### Deal Fields

| Field | Description |
|-------|-------------|
| Name | Worker's full name |
| Amount | Total deal value |
| TSV (Total Stage Value) | Amount at current stage |
| Hours | Estimated working hours |
| Rate | Hourly or contract rate |
| Recruitment Agency | Agency supplying the worker |
| Consultant | Umbrella company/consultant name |
| Owner | Assigned sales team member |

### Deal Stages

Deals move through these stages:

| Stage | Description |
|-------|-------------|
| 📄 Doc Sent | Contract/documentation sent to worker |
| ✍️ Doc Signed | Contract signed by worker |
| ✅ Compliant | All compliance checks passed |
| 💳 Ready for Payment | Worker cleared for work |
| 💰 Paid | Payment received |

### Moving Deals

- **Drag & Drop**: Drag a deal card to a new stage on the Kanban board
- **Click**: Click on a deal to open details, then use the stage navigator at the top

### Stage Permissions

| Role | Can Move To |
|------|-------------|
| Sales Team | Doc Sent → Doc Signed → Compliant |
| Compliance Team | Any stage |

---

## Pipeline (Kanban Board)

The Kanban board provides a visual overview of your pipeline.

### Viewing the Board

1. Navigate to **Deals**
2. The Kanban board is the default view
3. Switch to **Table View** for a spreadsheet-style layout

### Board Features

- **Stage Columns**: Each stage has its own column
- **Deal Cards**: Show worker name, amount, and key details
- **Stage Totals**: Total value at each stage
- **Drag & Drop**: Move deals between stages
- **Load More**: Load additional deals in each stage

### Card Information

Each deal card displays:
- Worker name
- Deal amount
- Worker contact name
- Company name
- Owner name
- Days since creation

---

## Deal Details

### Opening a Deal

Click on any deal card to view its full details.

### Tabs

#### Overview
- Deal summary and key information
- Signable document wizard for e-signatures

#### Activities
- Tasks and notes
- Add follow-up activities

#### Welcome Email
- Send welcome emails to workers
- Track email history

#### History
- Complete audit trail of all changes
- Stage movements
- Owner changes
- Field updates

### Deal Sections

#### Deal Details
- Basic deal information
- Amount and financial details

#### Worker Details
- Personal information
- Contact details
- Bank information for payment

#### MDA Details
- Minimum Deductions Agreement setup
- Reference numbers
- Setup dates

#### Compliance Details
- Right to work documentation
- Proof of address
- Photo ID/Passport
- Tax code
- Starter checklist status

### Actions

- **Save Changes**: Save all modifications
- **Disregard**: Revert unsaved changes
- **Delete Media**: Remove uploaded documents

---

## Contacts

Manage worker and contractor contacts.

### Fields

| Field | Description |
|-------|-------------|
| First Name | Contact's first name |
| Last Name | Contact's last name |
| Email | Email address |
| Phone | Phone number |
| Gender | Gender (optional) |
| Date of Birth | Date of birth |
| Marital Status | Marital status (optional) |
| Address | Full postal address |
| NI Number | National Insurance number |
| Bank Name | Bank name |
| Account Number | Bank account number |
| Sort Code | Bank sort code |

### Managing Contacts

1. Navigate to **Contacts**
2. View all contacts in table format
3. Click a contact to view/edit details
4. Use filters to find specific contacts

---

## Companies

Manage recruitment agencies and umbrella companies.

### Company Types

- **Umbrella Company** - For umbrella workers
- **Churchill Knight Umbrella** - Specific umbrella service
- **Churchill Knight Associates** - Associate/contractor service
- **Other** - Any other company type

### Company Fields

| Field | Description |
|-------|-------------|
| Name | Company name |
| Address | Company address |
| Contacts | Associated contacts |

### Managing Companies

1. Navigate to **Companies**
2. View, create, or edit companies
3. Associate contacts with companies
4. Import companies from CSV

---

## Users & Teams

### User Roles

| Role | Description |
|------|-------------|
| Admin | Full system access |
| Sales Team | Can manage their own deals |
| Compliance Team | Can manage compliance stages |

### Teams

- Group users by function
- Assign team leads
- Manage team permissions

### User Management (Admin)

1. Navigate to **Administration** → **Users**
2. Create new users
3. Assign to teams
4. Set permissions

---

## Envelopes & Signatures

### Document Signing

Send documents for electronic signature using Signable integration.

### Creating an Envelope

1. Open a **Deal**
2. Go to **Overview** tab
3. Click **Create Envelope**
4. Select template and documents
5. Add signers
6. Send for signature

### Tracking Envelopes

1. Navigate to **Envelopes**
2. View all envelopes
3. Check status (Sent, Viewed, Signed, Completed)
4. Download signed documents

---

## Email Templates

### Email Designer

Create reusable email templates for worker communications.

### Template Fields

| Field | Description |
|-------|-------------|
| Name | Template name |
| Subject | Email subject line |
| Content | Email body (HTML supported) |

### Using Templates

1. Open a **Deal**
2. Go to **Welcome Email** tab
3. Select a template
4. Preview and send

---

## GDPR Compliance

### GDPR Dashboard

Admin users can access compliance tools.

### Features

- View data processing activities
- Export user data
- Manage consent records

### Requesting Data Export

1. Navigate to **My Account** → **Request My Data**
2. Submit export request
3. Receive data via email

---

## Notifications

### In-App Notifications

Bell icon shows unread notifications:
- Deal stage changes
- New tasks assigned
- Document status updates

### Marking as Read

- Click **Mark all as read** to clear all
- Click individual notification to mark single item

---

## Best Practices

### Deal Management

1. **Update Regularly** - Keep deal stages current
2. **Add Notes** - Log all important communications
3. **Complete Compliance** - Ensure all documents are uploaded
4. **Track Activity** - Log all deal-related activities

### Contacts

1. **Complete Profiles** - Fill in all available information
2. **Verify Details** - Double-check bank and NI details
3. **Keep Updated** - Update contact information when changed

### Compliance

1. **Document Everything** - Upload all required documents
2. **Check Status** - Monitor compliance stage for each deal
3. **Flag Issues** - Address compliance problems immediately

---

## Troubleshooting

### Deals Not Moving

- Check your permissions (Sales can only move to allowed stages)
- Ensure all required fields are complete

### Documents Not Uploading

- Check file size (max 20MB)
- Supported formats: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG

### Can't See Deals

- Check filters are not applied
- Verify you are assigned as owner (Sales) or have admin access

---

## Support

For technical issues, contact your system administrator.

---

*Document Version: 1.0*
*Last Updated: 2026-06-15*
