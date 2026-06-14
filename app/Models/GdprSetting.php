<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GdprSetting extends Model
{
    protected $fillable = [
        'entity_type',
        'retention_months',
        'is_enabled',
        'custom_action',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'retention_months' => 'integer',
    ];

    public static function getRetentionFor(string $entityType): ?self
    {
        return self::where('entity_type', $entityType)
            ->where('is_enabled', true)
            ->first();
    }
}
