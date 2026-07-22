<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Remittance extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id',
        'user_id',
        'amount',
        'date_added',
        'status',
        'deal_owner',
        'company_id',
        'margin_agreed',
        'hours',
        'rate',
        'we_date',
        'shirft_date',
        'remarks',
        'week_no',
        'from',
        'invoice',
        'batch',
        'agency_funds',
        'payment_status',
        'compliance',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'margin_agreed' => 'decimal:2',
            'rate' => 'decimal:2',
            'hours' => 'decimal:2',
            'date_added' => 'date',
            'we_date' => 'date',
            'shirft_date' => 'date',
            'compliance' => 'boolean',
            'week_no' => 'integer',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deal_owner');
    }
}
