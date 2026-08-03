<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class EmailTemplate extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'name',
        'subject',
        'body',
        'is_html',
        'editor_mode',
        'sections',
        'description',
        'is_active',
        'internal_company',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_html' => 'boolean',
            'sections' => 'array',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('builder_images');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function logs(): HasMany
    {
        return $this->hasMany(
            DealEmailLog::class
        );
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(
            EmailTemplateAttachment::class
        );
    }
}
