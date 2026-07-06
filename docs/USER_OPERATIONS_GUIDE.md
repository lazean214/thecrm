# User Operations Guide — The CRM

## 1. System Overview

The CRM is a Laravel 13 + Livewire 4 application for managing deals, contacts, companies, and compliance workflows. It supports electronic document signing (Signable), accounting integration (MyDigitalAccounts), role-based access control, and GDPR compliance.

---

## 2. Server Requirements (Shared Hosting)

### Minimum Requirements

| Requirement | Specification |
|-------------|---------------|
| Web Server | Apache 2.4+ (mod_rewrite, mod_headers) or Nginx |
| PHP | **8.3 or 8.4** |
| Database | **MySQL 8.0+** or MariaDB 10.6+ |
| Composer | 2.x |
| Node.js | 18+ (for asset builds only, can run locally) |
| NPM | 10+ |
| SSL | Required (Let's Encrypt or paid certificate) |

### Required PHP Extensions

- `bcmath`
- `ctype`
- `curl`
- `dom`
- `fileinfo`
- `filter`
- `gd` or `imagick` (for media thumbnails)
- `iconv`
- `intl`
- `json`
- `libxml`
- `mbstring`
- `openssl`
- `pdo_mysql`
- `posix`
- `redis` (optional — recommended for cache/queue)
- `session`
- `simplexml`
- `tokenizer`
- `xml`
- `xmlreader`
- `xmlwriter`
- `zip`

> **Note:** Most shared hosting panels (cPanel, Plesk, DirectAdmin) offer these extensions via PHP selector. Request `imagick` if image thumbnails are needed.

### Shared Hosting File Structure

```
public_html/
├── public/              ← Point your domain here
│   ├── index.php        ← Entry point
│   ├── .htaccess
│   ├── build/
│   └── storage/         ← Symlink to ../storage/app/public
├── app/
├── bootstrap/
├── config/
├── database/
├── Modules/
├── resources/
├── routes/
├── storage/
│   ├── app/
│   │   ├── private/     ← Media uploads (not publicly accessible)
│   │   └── public/      ← Public files (linked via storage:link)
│   ├── framework/
│   ├── logs/
│   └── media-library/   ← Spatie media conversions
├── vendor/
├── .env                 ← NOT in public_html
├── artisan
├── composer.json
└── package.json
```

### Shared Hosting Checklist

- [ ] PHP 8.3/8.4 active with all required extensions
- [ ] Document root set to `/public` subfolder
- [ ] MySQL database created with charset `utf8mb4` + `utf8mb4_unicode_ci`
- [ ] `.env` file configured (database, mail, app URL, API keys)
- [ ] Queue worker running (see section 4.2)
- [ ] Cron job for scheduler (see section 4.1)
- [ ] Storage symlink created: `php artisan storage:link`
- [ ] Asset build deployed: `npm install && npm run build`
- [ ] SSL certificate installed and enforced
- [ ] File permissions: `755` for directories, `644` for files, `775` for `storage/` and `bootstrap/cache/`

---

## 3. User Roles & Permissions

| Role | Scope | Capabilities |
|------|-------|-------------|
| **Super Admin** | Full system | All access, user management, role assignment, settings |
| **Compliance** | All deals (read-only + compliance actions) | View all deals, mark deals Compliant, view audit logs |
| **Sales** | Own deals only | Create/edit deals, manage contacts/companies, send documents, mark Paid |
| **Finance** | Payment stage deals | View deals in Ready for Payment / Paid stages |

### Permission Mapping

| Action | Super Admin | Compliance | Sales | Finance |
|--------|:-----------:|:----------:|:-----:|:-------:|
| Manage users | ✅ | — | — | — |
| View all deals | ✅ | ✅ | Own only | ✅ |
| Create/Edit deals | ✅ | — | ✅ | — |
| Send for signing | ✅ | — | ✅ | — |
| Mark Compliant | ✅ | ✅ | — | — |
| Mark Paid | ✅ | — | ✅ | ✅ |
| Manage settings | ✅ | — | — | — |
| View audit logs | ✅ | ✅ | Own only | Own only |
| GDPR exports | ✅ | ✅ | — | — |
| Import/Export CSV | ✅ | — | ✅ | ✅ |

---

## 4. System Processes

### 4.1 Scheduled Tasks (Cron Jobs)

The following cron entry **must** be set up on the server:

```
* * * * * cd /home/user/public_html && php artisan schedule:run >> /dev/null 2>&1
```

**What runs on schedule:**

| Frequency | Command | Purpose |
|-----------|---------|---------|
| Every minute | `schedule:run` | Heartbeat — dispatches all due tasks |
| Daily (midnight) | `model:prune` | Prunes expired tokens/sessions |
| Daily | GDPR anonymization | Anonymises contacts/companies past retention period |
| Hourly | `queue:restart` | Gracefully restarts queue worker |
| Daily | `pulse:check` | Laravel Pulse data recording |

### 4.2 Queue Worker

The queue worker processes background jobs (email sending, document signing, media conversions, imports).

**Shared hosting (cPanel):** Use the "Cron Jobs" feature or NodeManager to run:

```
* * * * * cd /home/user/public_html && php artisan queue:work --sleep=3 --tries=3 --max-time=3600 >> /dev/null 2>&1
```

If your host supports **PHP CLI as a service**: request a persistent process for:

```
php artisan queue:work --tries=3
```

**Jobs processed by the queue:**

- Email notifications (deal stage changes, signing requests)
- Signable envelope creation and status callbacks
- MyDigitalAccounts company sync
- Media library conversions (thumbnails, responsive images)
- GDPR anonymisation tasks
- CSV/Excel imports

> **Important:** Without a running queue worker, the following **will not work**: email sending, document signing status updates, media thumbnail generation, file imports.

### 4.3 Storage & Media

**Storage drivers (from `.env`):**

```
FILESYSTEM_DISK=local         # Default for uploaded files
FILESYSTEM_CLOUD=s3           # Optional: AWS S3 for cloud storage
```

**On shared hosting:**
- Media files are stored in `storage/app/private/` (not publicly accessible)
- Public files go in `storage/app/public/` (symlinked to `public/storage/`)
- Run `php artisan storage:link` once after deployment

**Disk space considerations:**
- Signed documents and attachments can accumulate quickly
- Spatie MediaLibrary generates conversion thumbnails per upload
- Set up a monthly cleanup for orphaned media (see section 6)

### 4.4 Email Configuration

**Supported mail drivers:**

| Driver | Use Case | Env Variable |
|--------|----------|-------------|
| `smtp` | Production (Mailgun, Postmark, SendGrid, etc.) | `MAIL_MAILER=smtp` |
| `log` | Development / testing | `MAIL_MAILER=log` |
| Mailtrap API | Staging / QA | `MAIL_MAILER=mailtrap` |

**Transactional emails sent by the system:**
- Account registration & verification
- Password reset
- Deal stage change notifications
- Signing request notifications (via Signable webhooks)
- GDPR data export download links

---

## 5. Daily Operations

### 5.1 Deal Pipeline Workflow

```
Doc Sent → Doc Signed → Compliant → Ready for Payment → Paid
```

- Sales teams move deals through **Doc Sent → Doc Signed → Paid**
- Compliance team marks deals as **Compliant** (gate before payment)
- Finance team processes **Ready for Payment → Paid**

### 5.2 Common Tasks

| Task | How To |
|------|--------|
| Create a deal | Deals → Add Deal → fill form → Save |
| Add contacts to a deal | Open deal → Contacts → Link existing or Add new |
| Send document for signing | Open deal → Signable → Create Envelope → upload PDF → Send |
| Import contacts | Contacts → Import → upload CSV → Map columns → Import |
| Export data | Deals/Contacts → Export → choose format (CSV/Excel) |
| Generate GDPR report | Admin → GDPR → Export Request → select data scope |
| View audit history | Open deal → Audit Log |

### 5.3 Monitoring (Laravel Pulse)

The Pulse dashboard is available at `/pulse` (Super Admin only).

**What to monitor daily:**
- Slow requests (outliers > 500ms)
- Slow jobs (bottlenecks in queue)
- Cache hit/miss ratio
- Exception frequency
- Queue backlog (jobs pending)

---

## 6. Maintenance Procedures

### 6.1 Regular Maintenance

| Frequency | Task | Command |
|-----------|------|---------|
| Weekly | Check queue backlog | `php artisan queue:monitor` |
| Weekly | Review failed jobs | `php artisan queue:failed` |
| Monthly | Retry failed jobs | `php artisan queue:retry all` |
| Monthly | Clear orphaned media | `php artisan media-library:purge` |
| Monthly | Reindex search | `php artisan scout:import` (if Scout installed) |
| Quarterly | Optimize cache | `php artisan optimize` |
| Monthly | Check storage disk space | `df -h` / cPanel Disk Usage |

### 6.2 Deployment

```
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
npm ci && npm run build
php artisan storage:link        # if not already linked
```

### 6.3 Backup Strategy

| What | Frequency | Method |
|------|-----------|--------|
| Database | Daily | `mysqldump` or hosting backup tool |
| Storage (`storage/app/`) | Daily | rsync or S3 sync |
| `.env` | On change | Store securely (password manager) |
| Full application | Weekly | Git + storage snapshot |

### 6.4 Troubleshooting Common Issues

| Symptom | Likely Cause | Fix |
|---------|-------------|-----|
| White screen / 500 error | Storage permissions | `chmod -R 775 storage/ bootstrap/cache/` |
| Files not loading (404) | Missing storage link | Run `php artisan storage:link` |
| Emails not sending | Queue worker down | Start queue:work; check .env mail config |
| Signing status not updating | Webhook not reaching server | Check SIGNABLE_WEBHOOK_SECRET; verify route is public |
| Slow page loads | Cache not optimized | Run `php artisan optimize` |
| "Class not found" | Autoloader cache stale | `composer dump-autoload` |
| Login redirect loop | APP_URL mismatch | Update .env APP_URL to match domain |
| MEDIA library upload fails | PHP upload limits too low | Increase `upload_max_filesize`, `post_max_size` in php.ini |

### 6.5 PHP Limits (php.ini Recommendations)

```
upload_max_filesize = 32M
post_max_size = 32M
memory_limit = 256M
max_execution_time = 300
max_input_time = 300
```

---

## 7. Security Notes

- **Never** commit `.env` to Git
- Rotate API keys (Signable, MDA, Pusher) every 90 days
- Enforce HTTPS in production (set `SESSION_SECURE_COOKIE=true` in `.env`)
- Use strong passwords for all user accounts
- Enable 2FA for admin accounts (available via Profile → Security)
- Audit user access quarterly — revoke unused accounts
- Monitor failed login attempts via Pulse dashboard

---

## 8. External Services Reference

| Service | Purpose | Account Required |
|---------|---------|-----------------|
| **Signable** | Electronic document signing | [Signable](https://signable.co.uk) — paid subscription |
| **MyDigitalAccounts** | Accounting integration | MDA — client credentials provided by accounting team |
| **Mailtrap** | Email testing (optional) | [Mailtrap](https://mailtrap.io) — free tier available |
| **Pusher** | Real-time WebSocket updates | [Pusher](https://pusher.com) — free tier (200k messages/day) |
| **AWS S3** | Cloud file storage (optional) | AWS account with S3 bucket |

---

## 9. Contacts & Support

| Issue | Contact |
|-------|---------|
| Technical issues / bugs | Development team |
| Hosting / server issues | Hosting provider support |
| API key management | System administrator |
| User access / permissions | Super Admin |
| Accounting data sync | Finance team / MDA support |

---

*Document version 1.0 — Last updated July 2026*
