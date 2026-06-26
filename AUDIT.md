# Production Security Audit Report

**Date:** 2026-06-26  
**Application:** TheCRM (Laravel CRM)  
**Branch:** main  
**Commit:** 6900a86 (final audit)

---

## Executive Summary

This report details the findings from a comprehensive security audit of TheCRM production system. The audit identified **8 high-confidence vulnerabilities**, of which **6 are rated HIGH severity** and require immediate attention.

| Severity | Count |
|----------|-------|
| HIGH | 6 |
| MEDIUM | 5 |

---

## Critical Findings

### Finding 1: IDOR - Missing Authorization on API Resource Endpoints

**Severity:** HIGH  
**Confidence:** 95%  
**Category:** Broken Access Control / IDOR  
**Files:** 
- [routes/api.php](routes/api.php)
- [app/Http/Controllers/Api/DealController.php](app/Http/Controllers/Api/DealController.php)
- [app/Http/Controllers/Api/ContactController.php](app/Http/Controllers/Api/ContactController.php)
- [app/Http/Controllers/Api/UserController.php](app/Http/Controllers/Api/UserController.php)

**Description:** All API endpoints protected only by `auth:sanctum` lack authorization checks. Any authenticated user can view, modify, or delete any other user's resources. The `Deal` model has a `scopeVisibleTo()` method that applies Sales Team restrictions, but it is never called in API controllers.

**Exploit Scenario:**
```bash
# Authenticated as regular user - access ANY deal
curl -H "Authorization: Bearer $TOKEN" \
  https://thecrm.test/api/deals/1

# Modify ANY deal's amount
curl -X PATCH -H "Authorization: Bearer $TOKEN" \
  -d '{"amount": 999999}' \
  https://thecrm.test/api/deals/5

# Delete ANY contact
curl -X DELETE -H "Authorization: Bearer $TOKEN" \
  https://thecrm.test/api/contacts/3

# Delete ANY user
curl -X DELETE -H "Authorization: Bearer $TOKEN" \
  https://thecrm.test/api/users/456
```

**Recommendation:** Create and apply model policies:
```php
// app/Policies/DealPolicy.php
public function view(User $user, Deal $deal): bool
{
    if ($user->isSalesTeam()) {
        return $deal->user_id === $user->id;
    }
    return true;
}
```
Apply to routes: `Route::apiResource('deals', DealController::class)->middleware('can:view,deal')`

---

### Finding 2: Missing Webhook Signature Verification

**Severity:** HIGH  
**Confidence:** 95%  
**Category:** Authentication Bypass  
**File:** [app/Http/Controllers/SignableEnvelopeController.php](app/Http/Controllers/SignableEnvelopeController.php)

**Description:** The webhook handler (`handle()`) receives POST requests from Signable's external API but performs **no signature verification**. There is no check confirming that the incoming request actually originated from Signable.

**Exploit Scenario:** An attacker who can determine the webhook URL can send forged webhook payloads to:
- Mark any envelope as signed
- Set arbitrary download URLs
- Trigger unauthorized deal stage updates

**Recommendation:** Implement HMAC-SHA256 signature verification:
```php
$signature = $request->header('X-Signature');
$payload = $request->getContent();
if (! hash_equals($expected, hash_hmac('sha256', $payload, $secret))) {
    abort(403, 'Invalid webhook signature');
}
```

---

### Finding 3: Unauthenticated Signable API Routes

**Severity:** HIGH  
**Confidence:** 90%  
**Category:** Authentication Bypass  
**File:** [Modules/Signable/routes/api.php](Modules/Signable/routes/api.php)

**Description:** The entire `api/signable/*` route group uses only the `api` middleware, which does **not enforce authentication**. Routes include send, delete, cancel, remind, and batch-download operations.

**Exploit Scenario:**
```bash
# Send fraudulent envelopes (incurring costs)
curl -X POST https://thecrm.test/api/signable/envelopes

# Delete legitimate envelopes
curl -X DELETE https://thecrm.test/api/signable/envelopes/{fingerprint}

# Batch-download all signed documents
curl -X POST https://thecrm.test/api/signable/envelopes/batch-download
```

**Recommendation:** Add `auth:sanctum` middleware to all Signable API routes.

---

### Finding 4: Mass Assignment in GDPR Settings Import

**Severity:** HIGH  
**Confidence:** 95%  
**Category:** Mass Assignment  
**File:** [app/Http/Controllers/GdprAdminController.php:76-92](app/Http/Controllers/GdprAdminController.php#L76-L92)

**Description:** The `importSettings()` method decodes a JSON file and passes the decoded array directly to `GdprSetting::updateOrCreate()` without field whitelisting:

```php
GdprSetting::updateOrCreate(
    ['entity_type' => $setting['entity_type']],
    $setting  // Entire array used - no filtering
);
```

**Exploit Scenario:** A malicious admin can inject arbitrary columns including `id`, `created_at`, or any future columns added to the `gdpr_settings` table.

**Recommendation:**
```php
$allowed = ['entity_type', 'retention_months', 'is_enabled', 'custom_action'];
foreach ($content as $setting) {
    $filtered = array_intersect_key($setting, array_flip($allowed));
    GdprSetting::updateOrCreate(['entity_type' => $filtered['entity_type']], $filtered);
}
```

---

### Finding 5: API Rate Limiting Missing

**Severity:** HIGH  
**Confidence:** 95%  
**Category:** Insufficient Rate Limiting  
**File:** [routes/api.php](routes/api.php)

**Description:** API routes have no rate limiting configured. Attackers with valid tokens can make unlimited requests enabling:
- Brute force enumeration of resource IDs
- Mass data exfiltration
- Resource exhaustion attacks

**Recommendation:** Add throttle middleware:
```php
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function (): void {
    // all API routes
});
```

---

### Finding 6: user_id Modifiable via API

**Severity:** HIGH  
**Confidence:** 90%  
**Category:** Mass Assignment  
**Files:**
- [app/Http/Controllers/Api/DealController.php:24-26](app/Http/Controllers/Api/DealController.php#L24-L26)
- [app/Http/Requests/StoreDealRequest.php:38](app/Http/Requests/StoreDealRequest.php#L38)

**Description:** Authenticated users can reassign deals to any user via the `user_id` field, bypassing ownership restrictions.

**Exploit Scenario:**
```bash
POST /api/deals
{"name": "Test Deal", "user_id": 999}
```

**Recommendation:** Remove `user_id` from fillable/validation and force ownership:
```php
$data = $request->validated();
$data['user_id'] = $request->user()->id;
$deal = Deal::create($data);
```

---

## Medium Severity Findings

### Finding 7: Session Encryption Disabled

**Severity:** MEDIUM  
**Confidence:** 90%  
**Category:** Sensitive Data Exposure  
**File:** [config/session.php:50](config/session.php#L50)

**Description:** `SESSION_ENCRYPT=false` means all session data is stored in plain text.

**Recommendation:** Set `SESSION_ENCRYPT=true` in production `.env`.

---

### Finding 8: SSRF via Email Template URLs

**Severity:** MEDIUM  
**Confidence:** 85%  
**Category:** SSRF  
**Files:** [app/Services/EmailTemplateParser.php:81-101](app/Services/EmailTemplateParser.php#L81-L101)

**Description:** Button and image URLs in email templates are not validated against an allowlist. Attackers with template edit access could:
- Set button URLs to internal services (e.g., AWS metadata endpoint)
- Embed tracking pixels pointing to internal infrastructure

**Recommendation:** Add domain allowlisting for URLs in templates.

---

### Finding 9: 2FA Rate Limiter Nullable Key

**Severity:** MEDIUM  
**Confidence:** 85%  
**Category:** Rate Limiting Bypass  
**File:** [app/Providers/FortifyServiceProvider.php:88-90](app/Providers/FortifyServiceProvider.php#L88-L90)

**Description:** The 2FA rate limiter uses `$request->session()->get('login.id')` which could be null, creating an unlimited bypass bucket.

**Recommendation:**
```php
return Limit::perMinute(3)->by($request->session()->get('login.id') ?? $request->ip());
```

---

### Finding 10: Hardcoded Email Bypass in GDPR Gate

**Severity:** MEDIUM  
**Confidence:** 85%  
**Category:** Access Control  
**File:** [app/Providers/FortifyServiceProvider.php:43-46](app/Providers/FortifyServiceProvider.php#L43-L46)

**Description:** Hardcoded email addresses (`admin@thecrm.com`, `compliance@thecrm.com`) bypass proper team-based access control.

**Recommendation:** Remove hardcoded emails; rely only on `isComplianceTeam()` check.

---

### Finding 11: Error Message Disclosure

**Severity:** MEDIUM  
**Confidence:** 90%  
**Category:** Information Disclosure  
**File:** [Modules/Signable/app/Http/Controllers/Api/SignableWebhookController.php:89-94](Modules/Signable/app/Http/Controllers/Api/SignableWebhookController.php#L89-L94)

**Description:** The `serviceError()` method returns raw exception messages from Signable API to clients, potentially exposing internal architecture details.

**Recommendation:** Log full errors server-side and return generic messages to clients.

---

## Findings Excluded (Low Confidence)

The following were investigated but excluded due to low confidence or theoretical nature:

- SQL Injection via Kanban filters (Eloquent parameterization provides protection)
- PII validation in imports (data integrity issue, not directly exploitable)
- CSRF on stateful API (mitigated by Sanctum's built-in CSRF handling)

---

## Summary Table

| # | Severity | Category | Vulnerability | File(s) |
|---|----------|----------|---------------|---------|
| 1 | **HIGH** | IDOR | Missing authorization on API CRUD | routes/api.php, API Controllers |
| 2 | **HIGH** | Auth Bypass | Webhook signature missing | SignableEnvelopeController.php |
| 3 | **HIGH** | Auth Bypass | Unauthenticated Signable routes | Modules/Signable/routes/api.php |
| 4 | **HIGH** | Mass Assignment | GDPR settings import vulnerable | GdprAdminController.php |
| 5 | **HIGH** | Rate Limiting | No rate limits on API routes | routes/api.php |
| 6 | **HIGH** | Mass Assignment | user_id modifiable via API | DealController.php |
| 7 | **MEDIUM** | Data Exposure | Session encryption disabled | config/session.php |
| 8 | **MEDIUM** | SSRF | Email template URL validation | EmailTemplateParser.php |
| 9 | **MEDIUM** | Rate Limiting | 2FA rate limiter nullable key | FortifyServiceProvider.php |
| 10 | **MEDIUM** | Access Control | Hardcoded email bypass | FortifyServiceProvider.php |
| 11 | **MEDIUM** | Info Disclosure | Error message exposure | SignableWebhookController.php |

---

## Recommendations Priority

### Immediate (Fix Before Production)
1. ~~Add authorization policies to all API resource controllers~~ ✅ **FIXED**
2. ~~Implement webhook signature verification~~ ✅ **FIXED**
3. ~~Add authentication to Signable API routes~~ ✅ **FIXED**
4. ~~Whitelist fields in GDPR settings import~~ ✅ **FIXED**
5. ~~Add rate limiting to API routes~~ ✅ **FIXED**

### Short-term (Within 1 Sprint)
6. ~~Remove user_id from API fillable~~ ✅ **FIXED**
7. Enable session encryption
8. Add URL allowlisting to email templates

### Medium-term (Technical Debt)
9. Fix 2FA rate limiter nullable key
10. Remove hardcoded email bypasses
11. Sanitize error messages in webhook controller

---

## Remediation Summary

### Fixes Implemented (2026-06-26)

| # | Finding | Files Modified | Status |
|---|---------|---------------|--------|
| 1 | IDOR - Missing Authorization | Created: `app/Policies/{Deal,Contact,User}Policy.php`<br>Modified: `app/Http/Controllers/Api/{Deal,Contact,User}Controller.php`<br>Modified: `app/Models/Deal.php` | ✅ Complete |
| 2 | Webhook Signature Verification | Created: `Modules/Signable/app/Http/Middleware/VerifySignableWebhookSignature.php`<br>Modified: `routes/web.php`, `Modules/Signable/config/config.php` | ✅ Complete |
| 3 | Signable API Authentication | Modified: `Modules/Signable/routes/api.php` | ✅ Complete |
| 4 | GDPR Mass Assignment | Modified: `app/Http/Controllers/GdprAdminController.php` | ✅ Complete |
| 5 | API Rate Limiting | Modified: `routes/api.php` | ✅ Complete |
| 6 | user_id Mass Assignment | Modified: `app/Http/Requests/{Store,Update}DealRequest.php`<br>Modified: `app/Http/Controllers/Api/DealController.php` | ✅ Complete |

### Configuration Required for Production

Add to production `.env`:
```
SIGNABLE_WEBHOOK_SECRET=<your-webhook-secret>
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
```

---
*Report generated: 2026-06-26*
*Last updated: 2026-06-26*
