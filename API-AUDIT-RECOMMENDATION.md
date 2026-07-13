# API Audit & Recommendation for External System Integration

## Current API State

### Protected Routes (`routes/api.php`)

All API endpoints require `auth:sanctum` middleware - **no public access**:

| Method | Endpoint | Controller Action | Permissions |
|--------|----------|-------------------|-------------|
| GET | `/api/contacts` | ContactController@index | Admin: all contacts<br>Sales Team: contacts linked to their deals |
| POST | `/api/contacts` | ContactController@store | Any authenticated user |
| GET | `/api/contacts/{contact}` | ContactController@show | Admin: all<br>Sales Team: linked only |
| PATCH | `/api/contacts/{contact}` | ContactController@update | Admin: all<br>Sales Team: linked only |
| DELETE | `/api/contacts/{contact}` | ContactController@destroy | Admin only |
| GET | `/api/deals` | DealController@index | Admin: all deals<br>Sales Team: own deals |
| POST | `/api/deals` | DealController@store | Any authenticated user (auto-assigned) |
| GET | `/api/deals/{deal}` | DealController@show | Admin: all<br>Sales Team: own only |
| PATCH | `/api/deals/{deal}` | DealController@update | Admin: all<br>Sales Team: own only |
| DELETE | `/api/deals/{deal}` | DealController@destroy | Admin only |
| GET | `/api/users` | UserController@index | Admin only |
| POST | `/api/users` | UserController@store | Admin only |
| GET | `/api/users/{user}` | UserController@show | Own profile or Admin |
| PATCH | `/api/users/{user}` | UserController@update | Own profile or Admin |
| DELETE | `/api/users/{user}` | UserController@destroy | Admin only |
| GET | `/api/deals/kanban` | KanbanController@index | Admin: all<br>Sales Team: own deals |
| PATCH | `/api/deals/kanban/{deal}/stage` | KanbanController@updateStage | Admin + Sales Team owners |

### Current Authentication Issues for External Access

1. **No `HasApiTokens` trait** - `App\Models\User` uses `HasRoles` but NOT `HasApiTokens` from Laravel Sanctum. This means:
   - `createToken()` method is NOT available on User model
   - Personal access tokens cannot be generated

2. **No API token endpoints exist** - There's no route for external systems to:
   - Obtain a token (login)
   - Register a client
   - Refresh/exchange tokens

3. **Tests use session auth** - All tests use `actingAs()` (session-based), not token-based authentication

4. **CORS configured but limited** - Only allows `FRONTEND_URL` env (defaults to `localhost:3000`)

---

## Recommendation: One-Time Client Setup with Token Authentication

For external systems with users not registered in this system, implement the following:

### Option A: Personal Access Tokens (Recommended)

**Changes needed:**

1. **Add `HasApiTokens` to User model** (`app/Models/User.php:24`)

```php
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'notification_preferences'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    use HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable, HasApiTokens;
```

2. **Create a setup endpoint for external client registration** (`routes/api.php`)

```php
// Add BEFORE the auth middleware group for public access
Route::post('/client/setup', [ClientSetupController::class, 'store']);

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function (): void {
    // ... existing routes
});
```

3. **Create `ClientSetupController`** to provision external clients:

The controller should:
- Validate a setup key/secret (one-time configuration)
- Create a dedicated user account for the external system
- Generate and return a Sanctum token
- Return API access instructions

### Option B: API Key Approach (Alternative)

For simpler integration without user context:

1. Add `api_key` column to a dedicated `external_clients` table
2. Create middleware that validates API keys against this table
3. Add routes that bypass `auth:sanctum` for dedicated API key endpoints

---

## Usage for External Consumers

### After implementing Option A:

```bash
# One-time setup (requires predefined secret)
curl -X POST http://thecrm.test/api/client/setup \
  -H "Content-Type: application/json" \
  -d '{"setup_key": "YOUR_SETUP_SECRET"}'

# Response contains:
# {"token": "xxx|yyy...", "user_id": 123}

# Use token for all API calls
curl http://thecrm.test/api/deals \
  -H "Authorization: Bearer xxx|yyy..."
```

## Consumer Guide: Accessing from Another Laravel Application

To integrate this API into another Laravel application, use Laravel's built-in HTTP client.

### 1. Configure Environment

In the consumer Laravel app, add to `.env`:

```env
CRM_API_BASE_URL=http://thecrm.test
CRM_API_SETUP_KEY=your-setup-key-here
```

And in `config/services.php`:

```php
'crm' => [
    'base_url' => env('CRM_API_BASE_URL'),
    'setup_key' => env('CRM_API_SETUP_KEY'),
],
```

### 2. One-Time Token Registration

Run this once (e.g., in a command or seeder) to obtain a token:

```php
use Illuminate\Support\Facades\Http;

$response = Http::post(config('services.crm.base_url').'/api/client/setup', [
    'setup_key' => config('services.crm.setup_key'),
    'client_name' => config('app.name'), // identifies the consumer
]);

throw_unless($response->successful(), $response->throw());

$token = $response->json('token');

// Store the token securely, e.g. in config/services.php or .env
```

**Important:** Save the returned token permanently — it will **not** be shown again.

### 3. Make Authenticated API Calls

```php
use Illuminate\Support\Facades\Http;

$response = Http::withToken($token)
    ->get(config('services.crm.base_url').'/api/deals');

$deals = $response->json();
```

### 4. Full Example: API Client Class

Create an API client class for reusability:

```php
<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class CrmApiClient
{
    private string $baseUrl;
    private string $token;

    public function __construct()
    {
        $this->baseUrl = config('services.crm.base_url');
        $this->token = config('services.crm.token'); // stored after setup
    }

    private function client(): PendingRequest
    {
        return Http::withToken($this->token)
            ->baseUrl($this->baseUrl)
            ->acceptJson()
            ->throw();
    }

    public function getContacts(): array
    {
        return $this->client()->get('/api/contacts')->json();
    }

    public function getDeals(): array
    {
        return $this->client()->get('/api/deals')->json();
    }

    public function getDeal(int $id): array
    {
        return $this->client()->get("/api/deals/{$id}")->json();
    }

    public function createDeal(array $data): array
    {
        return $this->client()->post('/api/deals', $data)->json();
    }

    public function updateDeal(int $id, array $data): array
    {
        return $this->client()->patch("/api/deals/{$id}", $data)->json();
    }

    public function getUsers(): array
    {
        return $this->client()->get('/api/users')->json();
    }

    public function updateKanbanStage(int $dealId, string $stage): array
    {
        return $this->client()->patch("/api/deals/kanban/{$dealId}/stage", [
            'stage' => $stage,
        ])->json();
    }
}
```

Usage:

```php
$crm = app(CrmApiClient::class);
$deals = $crm->getDeals();
```

### 5. Queue-Safe Usage (for Jobs)

When calling from a queued job, keep the client stateless:

```php
use App\Services\CrmApiClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SyncDealsFromCrm implements ShouldQueue
{
    use Dispatchable;

    public function handle(CrmApiClient $crm): void
    {
        $deals = $crm->getDeals();

        foreach ($deals as $deal) {
            // process each deal
        }
    }
}
```

### 6. Error Handling

Laravel's HTTP client throws `Illuminate\Http\Client\RequestException` on 4xx/5xx. Catch and handle:

```php
use Illuminate\Http\Client\RequestException;

try {
    $deals = $crm->getDeals();
} catch (RequestException $e) {
    Log::error('CRM API request failed', [
        'status' => $e->response->status(),
        'body' => $e->response->body(),
    ]);

    throw $e; // or fail the job gracefully
}
```

### Current Limitations (if not implemented)

External systems CANNOT access the API because:
- No token generation mechanism exists
- All routes reject unauthenticated requests

---

## Rate Limiting

Currently: `throttle:60,1` = 60 requests per minute per user

Consider increasing for external sync operations:
- Add `throttle:api-sync` with higher limits for specific endpoints
- Or create a separate rate limit tier for client tokens

---

## Next Steps

1. Add `HasApiTokens` trait to User model
2. Create database migration for `personal_access_tokens` table (usually auto-created)
3. Create `ClientSetupController` with one-time setup logic
4. Update CORS to include external system's domain if needed
5. Document the setup process for external developers