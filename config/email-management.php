<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Operating Mode
    |--------------------------------------------------------------------------
    |
    | Controls what actions the skill can take:
    |   observe    — categorizes and records, never modifies the mailbox
    |   suggest    — surfaces recommendations for user approval
    |   autonomous — applies rules automatically (future)
    |
    */

    'operating_mode' => env('EMAIL_MANAGEMENT_MODE', 'observe'),

    /*
    |--------------------------------------------------------------------------
    | Layer Toggles
    |--------------------------------------------------------------------------
    |
    | Enable or disable each intelligence layer independently.
    |
    */

    'triage_enabled' => (bool) env('EMAIL_TRIAGE_ENABLED', true),
    'attention_enabled' => (bool) env('EMAIL_ATTENTION_ENABLED', false),
    'anomaly_enabled' => (bool) env('EMAIL_ANOMALY_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Notification Priority
    |--------------------------------------------------------------------------
    |
    | How aggressively to surface findings to the user:
    |   eager  — notify immediately on high-priority items
    |   normal — batch into digests
    |   quiet  — only respond when asked
    |
    */

    'notification_priority' => env('EMAIL_NOTIFICATION_PRIORITY', 'quiet'),

    /*
    |--------------------------------------------------------------------------
    | Processing Intervals
    |--------------------------------------------------------------------------
    |
    | How often each background intelligence process runs (in minutes).
    |
    */

    'intervals' => [
        'triage' => (int) env('EMAIL_TRIAGE_INTERVAL', 5),
        'attention' => (int) env('EMAIL_ATTENTION_INTERVAL', 15),
        'anomaly' => (int) env('EMAIL_ANOMALY_INTERVAL', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Categorization Model
    |--------------------------------------------------------------------------
    |
    | The LLM model used for email categorization. Uses provider:model format.
    | Haiku is recommended for speed and cost on high-volume categorization.
    |
    */

    'categorization_model' => env('EMAIL_CATEGORIZATION_MODEL', 'anthropic:claude-haiku-4-5-20251001'),

    /*
    |--------------------------------------------------------------------------
    | Categories
    |--------------------------------------------------------------------------
    |
    | Default categories for email triage. Can be extended per-user via
    | triage rules.
    |
    */

    'categories' => [
        'action_required',
        'needs_reply',
        'informational',
        'newsletter',
        'notification',
        'spam',
        'personal',
        'financial',
        'calendar',
    ],

    /*
    |--------------------------------------------------------------------------
    | Thresholds
    |--------------------------------------------------------------------------
    |
    | Confidence thresholds for categorization and attention surfacing.
    |
    */

    'thresholds' => [
        'categorization_min_confidence' => 0.7,
        'attention_priority_threshold' => 0.8,
    ],

    /*
    |--------------------------------------------------------------------------
    | Attention Defaults
    |--------------------------------------------------------------------------
    |
    | Default attention surfacing behavior.
    |
    */

    'attention' => [
        'max_items_per_digest' => 10,
        'stale_after_hours' => 48,
    ],

];
