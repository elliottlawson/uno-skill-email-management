<?php

declare(strict_types=1);

namespace ElliottLawson\EmailManagement\Models;

use Illuminate\Database\Eloquent\Model;

class TriageRule extends Model
{
    protected $connection = 'skill_email-management';

    protected $fillable = [
        'user_id',
        'name',
        'match_type',
        'match_value',
        'target_category',
        'auto_acknowledge',
        'priority_override',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'auto_acknowledge' => 'boolean',
            'active' => 'boolean',
        ];
    }
}
