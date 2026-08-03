<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignableEnvelope extends Model
{
    protected $fillable = [
        'deal_id',
        'envelope_fingerprint',
        'title',
        'status',
        'download_url',
        'queued_at',
        'completed_at',
    ];

    protected $casts = [
        'queued_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the parental business deal this envelope belongs to.
     */
    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }
}
