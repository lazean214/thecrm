# Production Deployment Guide for Namecheap Shared Hosting

## Pre-Deployment Checklist

### 1. Environment Configuration (DONE)

Your `.env` has been updated with production settings:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `LOG_LEVEL=error`
- `SESSION_ENCRYPT=true`
- `SESSION_SECURE_COOKIE=true`
- `SESSION_HTTP_ONLY=true`
- `SESSION_DRIVER=file`
- `CACHE_STORE=file`
- `QUEUE_CONNECTION=sync`

### 2. Create Production .env on Server

**IMPORTANT:** Never commit production credentials to git.

On your Namecheap server, edit `.env` with real values:

```env
APP_URL=https://yourdomain.com

# Database credentials from Namecheap cPanel
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_crm_database
DB_USERNAME=your_db_username
DB_PASSWORD=your_secure_password

# SMTP credentials from Namecheap or external provider
MAIL_MAILER=smtp
MAIL_HOST=mail.yourdomain.com
MAIL_PORT=587
MAIL_USERNAME=your-email@yourdomain.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=STARTTLS
MAIL_FROM_ADDRESS=noreply@yourdomain.com

# API Keys (get from respective services)
SIGNABLE_API_KEY=your_signable_api_key
SIGNABLE_API_SECRET=your_signable_secret
SIGNABLE_API_USER_ID=your_user_id

MYDIGITALACCOUNTS_CLIENT_ID=your_client_id
MYDIGITALACCOUNTS_CLIENT_SECRET=your_client_secret
MYDIGITALACCOUNTS_API_KEY=your_api_key
```

### 3. Folder Permissions

Set via FTP or cPanel File Manager:

```
chmod 755 storage
chmod 755 storage/app
chmod 755 storage/app/public
chmod 755 storage/framework
chmod 755 storage/framework/cache
chmod 755 storage/framework/cache/data
chmod 755 storage/framework/sessions
chmod 755 storage/framework/views
chmod 755 storage/logs
chmod 755 bootstrap/cache
```

### 4. Enable HTTPS

In `public/.htaccess`, uncomment the HTTPS redirect:

```apache
# Force HTTPS (uncomment in production)
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 5. Set Up Cron for Scheduled Tasks

In Namecheap cPanel → Cron Jobs, add:

```bash
* * * * * php /home/username/public_html/artisan schedule:run >> /dev/null 2>&1
```

Replace `/home/username/public_html` with your actual path.

### 6. Optional: Configure Queue Worker

If you need background jobs, set up a cron to process them:

```bash
* * * * * php /home/username/public_html/artisan queue:work --stop-when-empty >> /dev/null 2>&1
```

Or use `--tries=3` for retry logic.

### 7. Clear Config Cache

After deploying, run these commands via SSH or Laravel scheduler:

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 8. Verify Sanctum Stateful Domains

Update `config/sanctum.php` or `.env`:

```env
SANCTUM_STATEFUL_DOMAINS=yourdomain.com,www.yourdomain.com
```

## File Structure for Shared Hosting

```
public_html/
├── .htaccess (Laravel public directory)
├── index.php (Laravel entry point)
├── web.config (if IIS, otherwise use .htaccess)
├── css/
├── js/
└── storage/ (symlink to ../storage)
```

**Important:** The `storage` folder should be accessible. If your host doesn't allow symlinks, copy the storage folder contents to `public_html/storage`.

## Troubleshooting

### 500 Internal Server Error
- Check `storage/logs/laravel.log` for errors
- Verify `.env` file exists and has correct permissions (644)
- Ensure `APP_DEBUG=true` temporarily to see errors

### Session Not Working
- Clear session files: `rm storage/framework/sessions/*`
- Check PHP version compatibility (needs 8.3+)

### Mail Not Sending
- Verify SMTP credentials
- Check if port 587 is blocked (try 465 with SSL)
- Namecheap may require app-specific passwords

### Database Connection Failed
- Ensure DB_HOST is `localhost` (not 127.0.0.1)
- Check database user has proper permissions

## Security Reminders

1. **Never commit `.env`** to version control
2. **Keep `APP_KEY`** secret
3. **Use strong database passwords**
4. **Enable 2FA** for admin accounts
5. **Regular backups** via cPanel

## Quick Reference: Commands for SSH

```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear

# Cache for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Maintenance mode
php artisan down
php artisan up

# Check logs
tail -f storage/logs/laravel.log

# Run migrations
php artisan migrate --force

# Check scheduler
php artisan schedule:list
```
