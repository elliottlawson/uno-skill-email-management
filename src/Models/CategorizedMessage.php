<?php

declare(strict_types=1);

namespace ElliottLawson\EmailManagement\Models;

use Illuminate\Database\Eloquent\Model;

class CategorizedMessage extends Model
{
    protected $connection = 'skill_email-management';

    protected $fillable = [
        'user_id',
        'mailbox_id',
        'message_id',
        'category',
        'confidence',
        'subject',
        'sender',
        'metadata',
        'message_date',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'float',
            'metadata' => 'array',
            'message_date' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }
}
