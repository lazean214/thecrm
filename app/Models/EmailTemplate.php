<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function logs()
    {
        return $this->hasMany(
            DealEmailLog::class
        );
    }

    public function attachments()
    {
        return $this->hasMany(
            EmailTemplateAttachment::class
        );
    }
}
