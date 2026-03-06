<?php

declare(strict_types=1);

namespace ElliottLawson\EmailManagement\Models;

use Illuminate\Database\Eloquent\Model;

class AttentionItem extends Model
{
    protected $connection = 'skill_email-management';

    protected $fillable = [
        'user_id',
        'mailbox_id',
        'message_id',
        'priority',
        'reason',
        'category',
        'subject',
        'sender',
        'acknowledged',
        'acknowledged_at',
        'surfaced_at',
    ];

    protected function casts(): array
    {
        return [
            'acknowledged' => 'boolean',
            'acknowledged_at' => 'datetime',
            'surfaced_at' => 'datetime',
        ];
    }
}
