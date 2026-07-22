<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAssistantLog extends Model
{
    protected $fillable = [
        'user_id',
        'tool',
        'duration',
        'token_usage',
        'question',
        'response',
    ];

    /**
     * Get the user that owns the log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
