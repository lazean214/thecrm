<?php

// app/Models/DealHistory.php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealHistory extends Model
{
    protected $fillable = [
        'deal_id',
        'user_id',
        'action',
        'field',
        'old_value',
        'new_value',
        'details',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOfAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    public function scopeStageChanges(Builder $query): Builder
    {
        return $query->where('action', 'stage_moved');
    }

    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
