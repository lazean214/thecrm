<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplateAttachment extends Model
{
    protected $fillable = [
        'email_template_id',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
    ];

    public function template()
    {
        return $this->belongsTo(
            EmailTemplate::class,
            'email_template_id'
        );
    }
}
