<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GdprExportRequest extends Model
{
    protected $fillable = [
        'user_id',
        'download_token',
        'file_path',
        'exported_at',
        'expires_at',
    ];

    protected $casts = [
        'exported_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
