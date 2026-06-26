<?php

namespace App\Models;

use App\Enums\DealStage;
use App\Traits\LogsDealHistory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Deal extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, LogsDealHistory;

    protected $fillable = [
        'name',
        'amount',
        'stage',
        'hours',
        'rate',
        'recruitment_agency',
        'consultant_name',
        'agency_deal_value',
        'margin_agreed',
        'date_sent',
        'date_signed',
        'who_signed',
        'signed_doc',
        'right_to_work',
        'proof_of_address',
        'photo_id_passport',
        'mda_setup',
        'mda_reference_number',
        'date_set_up',
        'remittance_received',
        'date_logged',
        'user_id',

        // Compliance
        'starter_checklist_recieved_date',
        'starter_form',
        'tax_code',
        'contract_recieved_date',
        'stage_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'stage' => DealStage::class,
            'stage_updated_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(
            Contact::class,
            'contact_deal'
        )->withPivot('is_primary');
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(
            Company::class,
            'company_deal'
        )->withPivot('is_primary', 'agency_deal_value', 'margin_agreed');
    }

    public function primaryContact(): ?Contact
    {
        return $this->contacts()
            ->wherePivot('is_primary', true)
            ->first()
            ?? $this->contacts()->first();
    }

    public function primaryCompany(): ?Company
    {
        return $this->companies()
            ->wherePivot('is_primary', true)
            ->first()
            ?? $this->companies()->first();
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(
            DealEmailLog::class
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | CRM Visibility Scope
    |--------------------------------------------------------------------------
    */

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query;
        }

        // Admin can see all deals, Sales Team can only see their own
        if ($user->isSalesTeam() && ! $user->isAdmin()) {
            return $query->where(
                'user_id',
                $user->id
            );
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Media Collections
    |--------------------------------------------------------------------------
    */

    public function registerMediaCollections(): void
    {
        // MULTIPLE FILES
        $this->addMediaCollection('compliance_documents');

        $this->addMediaCollection('contract_documents');
    }

    public function signableEnvelopes(): HasMany
    {
        return $this->hasMany(SignableEnvelope::class);
    }
}
