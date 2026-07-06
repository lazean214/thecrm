## Getting Started

### Logging In

1. Navigate to `https://thecrm.test/login`
2. Enter your email and password
3. Click **Sign In**
4. If 2FA is enabled, enter the code from your authenticator app

### Your Dashboard

After logging in, you'll see your dashboard showing:

- **Deal summary** — count of deals in each pipeline stage
- **Recent activity** — latest updates across your deals
- **Quick actions** — create a deal, add a contact, import data

Use the sidebar to navigate between sections.

---

## Deals

The deal pipeline has 5 stages that a deal moves through:

```
Doc Sent → Doc Signed → Compliant → Ready for Payment → Paid
```

### Viewing Deals

- **Table view** — sortable columns, paginated. Click any deal to open it.
- **Kanban view** — drag-and-drop cards between pipeline stages.

Use the toggle at the top to switch between views.

### Creating a Deal

1. Click **Deals → Add Deal**
2. Fill in the required fields (deal name, value, company)
3. Assign contacts to the deal
4. Click **Save**

### Editing a Deal

- Open the deal and click **Edit**
- Update any fields
- Click **Save Changes**

### Moving a Deal Through the Pipeline

- In Kanban view: drag the card to the next stage column
- In Table view: open the deal and use the **Stage** dropdown

**Stage permissions:**

| Stage                         | Who can move it  |
| ----------------------------- | ---------------- |
| Doc Sent → Doc Signed         | Sales            |
| Doc Signed → Compliant        | Compliance only  |
| Compliant → Ready for Payment | Sales            |
| Ready for Payment → Paid      | Sales or Finance |

### Deleting a Deal

Open the deal → click **Delete** at the bottom → confirm. Deleted deals cannot be recovered.

---

## Contacts

Contacts are people associated with your deals and companies.

### Adding a Contact

1. Go to **Contacts → Add Contact**
2. Enter name, email, phone, and company
3. Click **Save**

### Linking a Contact to a Deal

1. Open the deal
2. Go to the **Contacts** tab
3. Click **Link Contact** — search and select, or create a new one

### Importing Contacts from CSV

1. Go to **Contacts → Import**
2. Select your CSV file
3. Map the columns (e.g., Name, Email, Phone)
4. Click **Import**

### Exporting Contacts

1. Go to **Contacts → Export**
2. Choose **CSV** or **Excel** format
3. The file will download

---

## Companies

### Adding a Company

1. Go to **Companies → Add Company**
2. Enter company name, registration number, address
3. Click **Save**

### Setting a Primary Contact

Each company can have one primary contact. Open the company → find the contact → click **Set as Primary**.

---

## Document Signing (Signable)

Send contracts and documents for electronic signature directly from a deal.

### Sending a Document for Signature

1. Open a deal
2. Go to the **Signable** tab
3. Click **Create Envelope**
4. Upload the PDF document
5. Add signers (they'll receive an email from Signable)
6. Click **Send**

### Tracking Signature Status

The envelope status updates automatically:

- **Draft** — not yet sent
- **Sent** — awaiting signatures
- **Completed** — all parties have signed
- **Cancelled** — sender cancelled the request
- **Expired** — signature deadline passed

### Downloading Signed Documents

Open the deal → **Signable** tab → click **Download** next to the completed envelope.

---

## Audit Log

Every change to a deal is logged for compliance.

### Viewing the Audit Log

1. Open a deal
2. Go to the **Audit Log** tab
3. See a timeline of who did what and when

Visible to: Sales (own deals), Compliance and Super Admin (all deals).

---

## GDPR & Data Privacy

### Requesting Your Data

1. Go to **Admin → GDPR**
2. Click **Request Data Export**
3. Select data types (contacts, deals, etc.)
4. You'll receive a download link when ready

### Data Retention

Personal data is automatically anonymised after the configured retention period. You will be notified before any data is anonymised.

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

---

## Notifications

- **In-app** — toast notifications appear at the top-right when things happen
- **Email** — you'll receive emails for deal stage changes and signing requests

### Managing Notification Preferences

Profile → **Notifications** → toggle which events you want email notifications for.

---

## Quick Reference

| Task                      | How to do it                                          |
| ------------------------- | ----------------------------------------------------- |
| Create a deal             | Deals → Add Deal                                      |
| Move deal to next stage   | Open deal → change Stage dropdown (or drag in Kanban) |
| Add a contact             | Contacts → Add Contact                                |
| Import contacts           | Contacts → Import → upload CSV                        |
| Send document for signing | Open deal → Signable tab → Create Envelope            |
| View audit history        | Open deal → Audit Log                                 |
| Change password           | Profile → Security                                    |
| Enable 2FA                | Profile → Security → Enable 2FA                       |
| Log out                   | Click avatar → Sign Out                               |

---

## Need Help?

If you encounter issues or need access to a feature you don't see, contact your system administrator.

---

_CRM User Guide v1.0_
