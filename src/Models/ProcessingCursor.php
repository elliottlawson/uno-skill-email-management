<?php

declare(strict_types=1);

namespace ElliottLawson\EmailManagement\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessingCursor extends Model
{
    protected $connection = 'skill_email-management';

    protected $fillable = [
        'user_id',
        'mailbox_id',
        'cursor_type',
        'cursor_value',
        'last_processed_at',
    ];

    protected function casts(): array
    {
        return [
            'last_processed_at' => 'datetime',
        ];
    }
}
