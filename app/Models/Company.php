<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'domain',
        'phone',
    ];

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(
            Contact::class,
            'company_contact'
        );
    }

    public function deals(): BelongsToMany
    {
        return $this->belongsToMany(
            Deal::class,
            'company_deal'
        )->withPivot('is_primary');
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(
            DealEmailLog::class
        );
    }
}
