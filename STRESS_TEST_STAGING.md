# Staging Environment Setup for Stress Testing

## Overview

This guide helps you create a safe staging environment to run stress tests without affecting your live CRM.

---

## Option 1: Create a Staging Environment with Herd

### 1. Create New Site in Herd

```bash
# In Herd UI or CLI
herd sites
# Add: thecrm-staging.test
# Point to: thecrm/staging/public
```

### 2. Set Up Staging Directory

```bash
# Create staging directory
mkdir -p staging/public staging/storage/framework/{sessions,views,cache}
mkdir -p staging/storage/logs

# Copy application files (exclude vendor, node_modules, .git)
rsync -av --exclude='vendor' --exclude='node_modules' --exclude='.git' --exclude='storage' thecrm/ staging/
```

### 3. Configure Staging Environment

```bash
cd staging
cp .env.example .env
```

Edit `staging/.env`:

```env
APP_NAME="theCrm Staging"
APP_ENV=staging
APP_DEBUG=true
APP_URL=https://thecrm-staging.test

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=thecrm_staging
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database

LOG_CHANNEL=single
LOG_LEVEL=debug
```

### 4. Create Staging Database

```sql
-- In MySQL
CREATE DATABASE thecrm_staging;
```

### 5. Clone Production Data

```bash
# Dump production database (run on production server)
mysqldump -u root -p thecrm_production > staging_dump.sql

# Import to staging
mysql -u root -p thecrm_staging < staging_dump.sql
```

### 6. Generate App Key & Run Migrations

```bash
cd staging
php artisan key:generate
php artisan migrate
```

---

## Option 2: Use Local Database with Subset of Production Data

### 1. Export Only Recent Data

```sql
-- Export last 30 days of deals, related contacts, companies
mysqldump -u root -p thecrm_production \
  deals contacts companies users teams \
  --where="created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)" \
  > recent_data.sql
```

### 2. Create Stress Test Environment Variables

Add to your `.env` for stress testing mode:

```env
# Enable stress test mode - adds rate limiting, monitoring
STRESS_TEST_MODE=true
```

### 3. Use a Separate Database Connection

```bash
# Create a separate .env.testing file
cp .env .env.staging
```

---

## Option 3: Docker-Based Staging

### docker-compose.staging.yml

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile.staging
    ports:
      - "8081:8080"
    environment:
      - APP_ENV=staging
      - APP_DEBUG=true
      - DB_CONNECTION=mysql
      - DB_HOST=db_staging
    volumes:
      - ./staging_data:/var/www/html/storage

  db_staging:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: thecrm_staging
      MYSQL_ROOT_PASSWORD: secret
    ports:
      - "3307:3306"
    volumes:
      - staging_mysql_data:/var/lib/mysql

volumes:
  staging_mysql_data:
```

---

## Running Stress Test on Staging

### 1. Start Queue Worker on Staging

```bash
# Terminal 1
cd staging
php artisan queue:work database --queue=default --sleep=3
```

### 2. Run the Stress Test

```bash
# Terminal 2
cd staging
php artisan crm:stress-test --users=50 --duration=300 --batch-size=10
```

### 3. Monitor with Horizon (if available)

```bash
cd staging
php artisan horizon
# Access at: https://thecrm-staging.test/horizon
```

---

## Production Safety Checklist

Before running any stress test:

- [ ] **NEVER** run on production database
- [ ] Set `APP_ENV=staging` (enables additional logging)
- [ ] Set `APP_DEBUG=true` (helps identify issues)
- [ ] Monitor server resources: `htop`, `docker stats`, or Herd dashboard
- [ ] Have database backup ready
- [ ] Notify team members about the test window
- [ ] Set up alerts for high CPU/memory usage
- [ ] Have rollback plan ready

---

## Quick Setup Script

Run this to set up a quick staging environment:

```bash
#!/bin/bash
# setup_staging.sh

STAGING_DIR="./staging"
DB_NAME="thecrm_staging"

echo "Creating staging directory..."
mkdir -p $STAGING_DIR

echo "Copying application files..."
rsync -av --exclude='vendor' --exclude='node_modules' --exclude='.git' --exclude='storage' . $STAGING_DIR/

echo "Creating staging database..."
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS $DB_NAME;"

echo "Configuring staging environment..."
cd $STAGING_DIR
cp .env.example .env
sed -i 's/APP_ENV=production/APP_ENV=staging/' .env
sed -i "s/DB_DATABASE=.*/DB_DATABASE=$DB_NAME/" .env

echo "Generating app key..."
php artisan key:generate

echo "Staging environment ready!"
echo "Next steps:"
echo "  1. Import production data: mysql -u root -p $DB_NAME < production_dump.sql"
echo "  2. Start queue: php artisan queue:work"
echo "  3. Run test: php artisan crm:stress-test --users=50 --duration=300"
```

---

## Monitoring During Stress Test

### System Resources

```bash
# CPU and memory
watch -n 1 'htop'

# Disk I/O
iotop

# MySQL connections
mysql -u root -p -e "SHOW PROCESSLIST;"
```

### Application Logs

```bash
tail -f staging/storage/logs/laravel.log
```

### Queue Monitoring

```bash
# Monitor queue table
watch -n 1 'mysql -u root -p -e "SELECT COUNT(*) FROM jobs;" thecrm_staging'

# Watch failed jobs
mysql -u root -p -e "SELECT * FROM failed_jobs ORDER BY created_at DESC LIMIT 10;" thecrm_staging
```
