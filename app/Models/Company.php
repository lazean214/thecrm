<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'name',
        'email',
        'domain',
        'phone',
    ];

    public function contacts()
    {
        return $this->belongsToMany(
            Contact::class,
            'company_contact'
        );
    }

    public function deals()
    {
        return $this->belongsToMany(
            Deal::class,
            'company_deal'
        )->withPivot('is_primary');
    }

    public function emailLogs()
    {
        return $this->hasMany(
            DealEmailLog::class
        );
    }
}
