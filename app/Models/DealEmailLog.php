<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DealEmailLog extends Model
{
    protected $fillable = [
        'deal_id',
        'contact_id',
        'company_id',
        'user_id',
        'email_template_id',
        'to_email',
        'subject',
        'body',
        'status',
        'sent_at',
        'error_message',
        'attachments',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'attachments' => 'array',
        ];
    }

    public function deal()
    {
        return $this->belongsTo(
            Deal::class
        );
    }

    public function contact()
    {
        return $this->belongsTo(
            Contact::class
        );
    }

    public function company()
    {
        return $this->belongsTo(
            Company::class
        );
    }

    public function template()
    {
        return $this->belongsTo(
            EmailTemplate::class,
            'email_template_id'
        );
    }

    public function user()
    {
        return $this->belongsTo(
            User::class
        );
    }
}
