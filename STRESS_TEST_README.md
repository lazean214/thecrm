# CRM Stress Test

Internal stress testing tool for theCrm to simulate concurrent user activity and monitor system performance.

## Files Created

- [CrmStressTestCommand.php](app/Console/Commands/CrmStressTestCommand.php) - Main Artisan command
- [CrmStressTestUserSimulationJob.php](app/Jobs/CrmStressTestUserSimulationJob.php) - Job for simulating user actions
- [StressTestSeeder.php](database/seeders/StressTestSeeder.php) - Seeder for test data

---

## Setup

### 1. Seed Test Data

Before running the stress test, you need test data:

```bash
# Run the stress test seeder (creates 50 users, 100 companies, ~300 contacts, 500 deals)
php artisan db:seed --class=StressTestSeeder

# Or run fresh with migration
php artisan migrate:fresh --seed --seeder=StressTestSeeder
```

### 2. Start Queue Worker (Queue Mode Only)

For servers with queue worker support (VPS, dedicated):

```bash
# Using database queue
php artisan queue:work database --sleep=3 --tries=3
```

For **shared hosting** (Namecheap, etc.), use `--sync` mode instead (see below).

---

## Usage

### Shared Hosting / Sync Mode (Recommended for Namecheap)

No queue worker needed - runs in foreground:

```bash
# Quick test: 50 users, 1 minute
php artisan crm:stress-test --sync --users=50 --duration=60 --batch-size=10

# Medium test: 30 users, 2 minutes
php artisan crm:stress-test --sync --users=30 --duration=120 --batch-size=10

# Full test: 50 users, 5 minutes
php artisan crm:stress-test --sync --users=50 --duration=300 --batch-size=10
```

### VPS / Dedicated Server (Queue Mode)

```bash
# Default: 50 users, 300 seconds, batch size 10
php artisan crm:stress-test

# 100 concurrent users, 5 minutes, batch size 20
php artisan crm:stress-test --users=100 --duration=300 --batch-size=20
```

### Parameter Reference

| Parameter | Default | Description |
|-----------|---------|-------------|
| `--users` | 50 | Number of concurrent simulated users |
| `--duration` | 300 | Test duration in seconds (5 minutes) |
| `--batch-size` | 10 | Number of operations per batch |
| `--sync` | false | Run synchronously (no queue worker needed) |

---

## Simulated Actions

Each simulated user performs random CRM actions with the following distribution:

| Action | Weight | Description |
|--------|--------|-------------|
| Fetch Deals | 40% | Paginated deal queries with random filters (stage, amount, owner) |
| Update Deal | 25% | Random deal updates (stage, amount, margin) |
| Search Contacts | 20% | Contact search queries |
| Batch Operations | 10% | Bulk operations on 5-10 deals |
| Generate Export | 5% | Export query simulation |

---

## Output

The command displays:

### During Test
- Live progress table with:
  - Elapsed time / remaining
  - Queue backlog count
  - Total requests processed
  - Total errors
  - Average response time (ms)
  - Requests per user

### Final Report
```
================================================================
STRESS TEST RESULTS
================================================================

+-------------------+------------------+
| Metric            | Value            |
+-------------------+------------------+
| Test Duration     | 300 seconds      |
| Total Requests    | 1,234            |
| Total Errors      | 0                |
| Error Rate        | 0.00%            |
| Avg Response Time | 12.34 ms         |
| Requests/Second   | 4.11             |
+-------------------+------------------+

Action Breakdown:
+--------------------------+----------+-------------+
| Action                   | Count    | Percentage  |
+--------------------------+----------+-------------+
| Fetch Deals (paginated)  | 494      | 40.0%       |
| Update Deal              | 308      | 25.0%       |
| Search Contacts          | 247      | 20.0%       |
| Batch Operations         | 123      | 10.0%       |
| Generate Export          | 62       | 5.0%        |
+--------------------------+----------+-------------+

Database Query Statistics:
+---------------+----------+
| Query Type    | Count    |
+---------------+----------+
| SELECT (est.) | 3,702    |
| UPDATE (est.) | 431      |
+---------------+----------+

Queue Statistics:
+------------------+----------+
| Metric           | Value    |
+------------------+----------+
| Final Queue Back | 0        |
| Queue Connection | database |
+------------------+----------+
```

---

## Monitoring

### Check Queue Status

```bash
# View pending jobs
php artisan queue:monitor database

# View failed jobs
php artisan queue:failed
```

### Check Application Logs

Logs are written to `storage/logs/laravel.log` with `[CRM Stress Test]` prefix.

### Database Query Monitoring

Enable query logging in `.env`:

```env
LOG_CHANNEL=single
LOG_LEVEL=debug
```

---

## Test Data Cleanup

After testing, you can remove stress test data:

```bash
# Delete stress test users
php artisan tinker --execute="App\Models\User::where('email', 'like', 'stress_user_%@test.com')->delete();"

# Delete stress test companies
php artisan tinker --execute="App\Models\Company::where('name', 'like', 'Stress Test Company %')->delete();"

# Delete stress test deals
php artisan tinker --execute="App\Models\Deal::where('name', 'like', 'Stress Test Deal %')->delete();"

# Delete stress test contacts
php artisan tinker --execute="App\Models\Contact::where('email', 'like', 'contact_%@test.com')->delete();"
```

Or simply:

```bash
# Fresh database with only stress test data
php artisan migrate:fresh --seed --seeder=StressTestSeeder
```

---

## Performance Tips

1. **Queue Connection**: Use `database` or `redis` for queue to avoid blocking
2. **Batch Size**: Increase `--batch-size` for higher throughput
3. **Database**: Ensure indexes exist on `user_id`, `stage`, `amount` columns
4. **N+1 Prevention**: The job uses `with()` for eager loading relationships

---

## Troubleshooting

### "No users found"
Run the seeder first: `php artisan db:seed --class=StressTestSeeder`

### Queue not processing
Start a worker: `php artisan queue:work`

### Out of memory
Reduce `--users` or increase PHP memory limit:

```bash
php -d memory_limit=512M artisan crm:stress-test --users=100
```

### Jobs timing out
The job has a 120-second timeout. If queries are slow, consider:
- Increasing `timeout` in the job
- Reducing `--batch-size`
- Optimizing database indexes
