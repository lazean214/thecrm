<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'street_address',
        'city',
        'state',
        'postal_code',
        'country',
        'ni_number',
        'bank',
        'account_number',
        'sort_code',
        'date_of_birth',
        'marital_status',
        'gender',
        'last_activity_at',
        'anonymised_at',
        'marked_for_deletion_on',
        'payroll_company',
        'payroll_source',
        'payroll_reference',
        'payroll_start_date',
        'payroll_status',
    ];

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(
            Company::class,
            'company_contact'
        );
    }

    public function deals(): BelongsToMany
    {
        return $this->belongsToMany(
            Deal::class,
            'contact_deal'
        )->withPivot('is_primary');
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(
            DealEmailLog::class
        );
    }
}
