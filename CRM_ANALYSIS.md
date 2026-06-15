# CRM System Analysis

A comprehensive technical analysis of the CRM application architecture, features, and data model.

---

## Executive Summary

This CRM is a Laravel-based deal management system designed for recruitment agencies and umbrella companies. It tracks workers through a 5-stage pipeline, manages contacts and companies, integrates electronic signatures, and provides GDPR compliance tools.

---

## Technical Stack

| Component | Technology |
|-----------|-------------|
| Backend Framework | Laravel 13 (PHP 8.4) |
| Frontend | Livewire 4, Alpine.js, Flux UI |
| Styling | Tailwind CSS v4 |
| Database | MySQL |
| Media Storage | Spatie Media Library |
| Authentication | Laravel Fortify + Passkeys |
| Email | Laravel Mailables |
| E-Signatures | Signable API Integration |
| Monitoring | Laravel Pulse |

---

## Data Model Architecture

### Core Entities

#### Deal (Central Entity)
The `Deal` model is the primary entity representing a worker placement or contract.

**Key Fields:**
- `name` - Worker/contractor name
- `amount` - Total deal value
- `stage` - Current pipeline stage (enum)
- `hours`, `rate` - Financial details
- `recruitment_agency` - Source agency
- `consultant_name` - Assigned consultant
- `user_id` - Owner (sales team member)
- `stage_updated_at` - Timestamp for stage transitions

**Relationships:**
- Many-to-Many with `Contact` (via `contact_deal` pivot)
- Many-to-Many with `Company` (via `company_deal` pivot)
- BelongsTo `User` (owner)
- HasMany `SignableEnvelope`
- HasMany `DealEmailLog`
- HasMany `DealHistory`

#### Contact
Represents workers/contractors with personal and banking details.

**Key Fields:**
- Personal: `first_name`, `last_name`, `email`, `phone`, `date_of_birth`, `gender`, `marital_status`
- Address: `street_address`, `city`, `state`, `postal_code`, `country`
- Financial: `ni_number`, `bank`, `account_number`, `sort_code`

#### Company
Represents recruitment agencies or umbrella companies.

**Key Fields:**
- `name`, `email`, `domain`, `phone`

#### User
System users with team-based role management.

**Key Methods:**
- `isAdmin()` - Hardcoded admin check
- `isSalesTeam()` - Checks "Sales Team" membership
- `isComplianceTeam()` - Checks "Compliance Team" membership
- `getAllowedDealStages()` - Returns stages user can move deals to

---

## Deal Pipeline Stages

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌──────────────────┐    ┌──────┐
│  Doc Sent   │───▶│ Doc Signed  │───▶│  Compliant  │───▶│ Ready for Payment│───▶│ Paid │
└─────────────┘    └─────────────┘    └─────────────┘    └──────────────────┘    └──────┘
```

| Stage | Enum Value | Description |
|-------|------------|-------------|
| Doc Sent | `doc sent` | Contract sent to worker |
| Doc Signed | `doc signed` | Contract signed |
| Compliant | `compliant` | All compliance checks passed |
| Ready for Payment | `ready for payment` | Cleared for work/payment |
| Paid | `paid` | Payment received |

---

## User Roles & Permissions

### Team-Based Access Control

| Role | Stage Access | Capabilities |
|------|--------------|--------------|
| **Sales Team** | Doc Sent → Doc Signed → Compliant | Manage own deals only |
| **Compliance Team** | All stages | Manage compliance stages |
| **Admin** | All stages | Full system access (hardcoded) |

### Permission Logic (`User.php:113-136`)
```php
public function getAllowedDealStages(): array
{
    if ($this->isComplianceTeam()) {
        return all DealStage::cases();
    }

    if ($this->isSalesTeam()) {
        return [DOC_SENT, DOC_SIGNED, COMPLIANT];
    }

    return all DealStage::cases(); // Default: no restrictions
}
```

### Deal Visibility Scope (`Deal.php:112-126`)
```php
public function scopeVisibleTo(Builder $query, ?User $user): Builder
{
    if (!$user) return $query;

    if ($user->isSalesTeam()) {
        return $query->where('user_id', $user->id);
    }

    return $query; // Non-sales see all
}
```

---

## Key Features

### 1. Kanban Board
- Visual pipeline with drag-and-drop deal movement
- Stage columns with deal counts and totals
- Card display: name, amount, contact, company, owner, age
- Optimistic UI updates with server validation

### 2. Deal Management
- Comprehensive deal forms with tabs:
  - **Overview** - Deal summary, Signable integration
  - **Activities** - Tasks and notes
  - **Welcome Email** - Email templates and history
  - **History** - Audit trail of all changes
- Compliance documentation uploads (Spatie Media Library)
- MDA (Minimum Deductions Agreement) tracking

### 3. Contact & Company Management
- Contact-Company relationships (many-to-many)
- Primary contact/company designation per deal
- Deal associations via pivot tables

### 4. Electronic Signatures
- Signable API integration
- Envelope creation and tracking
- Status monitoring (Sent → Viewed → Signed → Completed)

### 5. Email Templates
- Custom email templates with Builder support
- Template attachments
- Welcome email sending per deal

### 6. GDPR Compliance
- User data export requests
- Admin GDPR dashboard
- Consent management
- Data retention policies

### 7. Notifications
- In-app notifications via database channel
- Deal stage change notifications
- Compliance team alerts
- Deal owner notifications

### 8. Audit Trail
- `DealHistory` model tracks all changes
- `ActivityLog` for system-wide logging
- Stage movement history
- Field-level change tracking

---

## Database Schema

### Pivot Tables

| Table | Purpose |
|-------|---------|
| `company_contact` | Company ↔ Contact relationships |
| `company_deal` | Company ↔ Deal (with `is_primary`) |
| `contact_deal` | Contact ↔ Deal (with `is_primary`) |
| `team_user` | User ↔ Team membership |

### Key Indexes
- `deals.stage` - Pipeline queries
- `deals.user_id` - Owner filtering
- `deals.name` - Search optimization
- `deals.created_at` - Recent deals
- `contacts.email` - Email lookups

---

## API & Routes

### Public Routes
- `/` - Welcome page
- Authentication routes (Fortify)

### Authenticated Routes
```
/dashboard           - Pipeline overview
/deals              - Kanban/table view
/deals/{id}         - Deal detail
/contacts           - Contact management
/contacts/{id}      - Contact detail
/companies          - Company management
/designer           - Email template editor
/teams              - Team management
/users              - User administration
/admin/gdpr/*       - GDPR admin routes
/gdpr/export/*      - User data export
```

---

## Strengths

### 1. Clean Architecture
- Eloquent relationships properly defined
- Pivot tables for many-to-many relationships
- Scopes for query optimization
- Observer pattern for side effects

### 2. Role-Based Access
- Team-based permissions
- Deal visibility scoping
- Stage movement restrictions

### 3. Audit Capabilities
- `DealHistory` for deal changes
- `ActivityLog` for system events
- `DealEmailLog` for email tracking

### 4. Modern Stack
- Laravel 13 with PHP 8.4 features
- Livewire for reactive UI
- Flux UI components
- Spatie Media Library

### 5. Compliance Ready
- GDPR export functionality
- Data retention settings
- Consent tracking

---

## Areas for Improvement

### 1. Role-Based Access Control (Implemented ✅)
The admin check now uses Spatie Permissions:

```php
// User.php - Uses HasRoles trait
use Spatie\Permission\Traits\HasRoles;

public function isAdmin(): bool
{
    return $this->hasRole('admin');
}
```

**Roles Created:**
- `admin` - Full system access
- `sales` - Sales team members
- `compliance` - Compliance team members

**Seeders:**
- `RoleSeeder` - Creates roles
- `AdminUserSeeder` - Assigns admin role to admin user
- `TeamSeeder` - Assigns roles to users based on team membership

**Database Tables:**
- `roles` - Role definitions
- `permissions` - Permission definitions
- `model_has_roles` - User ↔ Role mapping
- `model_has_permissions` - User ↔ Permission mapping
- `role_has_permissions` - Role ↔ Permission mapping

**UI Updates:**
- Users page now shows roles with assign/remove functionality
- Teams page shows team members with their roles
- Visual distinction: Roles (purple) vs Teams (indigo)

### 2. Missing Deal Status
No explicit "archived" or "lost" status for deals. Dead deals remain in the pipeline.

### 3. Email Queue
Emails are sent synchronously. Consider async processing for better UX.

### 4. Deal Amount Tracking
Only one `amount` field. Consider:
- Amount at each stage
- Revenue recognition tracking
- Payment schedule

### 5. Reporting
Limited reporting capabilities. Consider:
- Revenue forecasting
- Conversion rates per stage
- Sales rep performance
- Average deal cycle time

### 6. File Storage
Uses local media library. Consider cloud storage (S3) for scalability.

### 7. API Layer
No REST API for mobile apps or third-party integrations.

---

## Security Considerations

| Area | Status | Notes |
|------|--------|-------|
| Authentication | ✅ Strong | Fortify + Passkeys + 2FA |
| Authorization | ⚠️ Basic | Team-based, needs role refinement |
| Data Encryption | ✅ | Laravel encryption |
| SQL Injection | ✅ | Eloquent ORM |
| XSS | ✅ | Blade auto-escaping |
| CSRF | ✅ | Laravel middleware |
| File Uploads | ⚠️ | Size validation, type checking |

---

## Performance Considerations

### Current Optimizations
- Database indexes on frequently queried columns
- `updateQuietly()` to prevent observer loops
- Optimistic UI updates
- Scope-based query filtering

### Potential Bottlenecks
- N+1 queries in deal listings (needs `with()` eager loading)
- Large media collections per deal
- Real-time updates without proper caching

---

## Conclusion

This CRM is a well-structured Laravel application suitable for recruitment agencies managing worker placements through a sales pipeline. The team-based role system, audit trails, and GDPR compliance features make it suitable for regulated industries. Key areas for enhancement include:

1. Replace hardcoded admin with proper RBAC
2. Add deal lifecycle statuses
3. Implement reporting/analytics
4. Consider API development for future mobile apps

---

*Analysis Date: 2026-06-15*
