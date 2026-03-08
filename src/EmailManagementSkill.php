<?php

declare(strict_types=1);

namespace ElliottLawson\EmailManagement;

use App\Contracts\HasSettings;
use App\Contracts\Skill;
use App\Enums\DangerLevel;
use App\Services\MailBridgeOAuthService;
use ElliottLawson\EmailManagement\Tools\GetEmailDigestTool;

class EmailManagementSkill implements HasSettings, Skill
{
    public function name(): string
    {
        return 'email-management';
    }

    public function description(): string
    {
        return 'Intelligent email triage, attention surfacing, and anomaly detection.';
    }

    public function dangerLevel(): DangerLevel
    {
        return DangerLevel::Moderate;
    }

    /**
     * @return array<int, \Prism\Prism\Tool>
     */
    public function tools(): array
    {
        return [
            GetEmailDigestTool::make(),
        ];
    }

    public function isConfigured(): bool
    {
        return MailBridgeOAuthService::isConfigured();
    }

    /**
     * @return array<string, string>
     */
    public function configurationRequirements(): array
    {
        return [
            'mail_bridge_url' => 'Mail Bridge server URL must be configured in system settings',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function triggers(): array
    {
        return [
            'triage',
            'attention',
            'digest',
            'categorize',
            'email management',
            'email triage',
            'email digest',
            'important emails',
            'email priority',
            'email summary',
        ];
    }

    public function alwaysLoad(): bool
    {
        return false;
    }

    public function systemPrompt(): ?string
    {
        $mode = config('email-management.operating_mode', 'observe');

        return <<<PROMPT
        ## Email Management Intelligence

        You have access to an intelligent email management layer that goes beyond raw mailbox access. This system provides:

        ### Capabilities
        - **Triage & Categorization**: Emails are automatically categorized (action_required, needs_reply, informational, newsletter, notification, spam, personal, financial, calendar)
        - **Digest Generation**: Summarized views of email activity across all mailboxes via the `get_email_digest` tool

        ### Current Operating Mode: {$mode}
        - **observe**: Read-only analysis — emails are categorized but never modified
        - **suggest**: Recommendations surfaced for user approval (future)
        - **autonomous**: Automatic rule application (future)

        ### How to Use
        When the user asks about email triage, digests, priorities, or attention items, use the `get_email_digest` tool to retrieve categorized email data. This is faster and more structured than browsing raw mail.

        Examples:
        - "What needs my attention?" → Use get_email_digest, highlight action_required and needs_reply
        - "Give me a digest" → Use get_email_digest with default 24h window
        - "What emails came in today?" → Use get_email_digest with hours=24

        For raw email operations (reading specific messages, sending, replying), the base Email skill's tools are more appropriate.
        PROMPT;
    }

    /**
     * @return array<int, array{key: string, label: string, description?: string, type: string, default?: mixed, suffix?: string, options?: array<int, array{value: string, label: string}>}>
     */
    public function settingsSchema(): array
    {
        return [
            [
                'key' => 'email-management.operating_mode',
                'label' => 'Operating Mode',
                'description' => 'Controls what actions the skill can take on your mailbox.',
                'type' => 'select',
                'default' => 'observe',
                'options' => [
                    ['value' => 'observe', 'label' => 'Observe — Read-only analysis, never modifies your mailbox'],
                    ['value' => 'suggest', 'label' => 'Suggest — Surfaces recommendations for your approval (coming soon)'],
                    ['value' => 'autonomous', 'label' => 'Autonomous — Applies rules automatically (coming soon)'],
                ],
            ],
            [
                'key' => 'email-management.triage_enabled',
                'label' => 'Inbox Triage',
                'description' => 'Automatically categorize incoming emails in the background.',
                'type' => 'toggle',
                'default' => true,
            ],
            [
                'key' => 'email-management.intervals.triage',
                'label' => 'Triage Interval',
                'description' => 'How often to check for new emails to categorize.',
                'type' => 'number',
                'default' => 5,
                'suffix' => 'minutes',
            ],
            [
                'key' => 'email-management.categorization_model',
                'label' => 'Categorization Model',
                'description' => 'LLM model used for email categorization. Faster models recommended for throughput.',
                'type' => 'select',
                'default' => 'anthropic:claude-haiku-4-5-20251001',
                'options' => $this->buildModelOptions(),
            ],
            [
                'key' => 'email-management.notification_priority',
                'label' => 'Notification Priority',
                'description' => 'How aggressively to surface email findings.',
                'type' => 'select',
                'default' => 'quiet',
                'options' => [
                    ['value' => 'quiet', 'label' => 'Quiet — Only shows email insights when you ask'],
                    ['value' => 'normal', 'label' => 'Normal — Includes email highlights in periodic digests'],
                    ['value' => 'eager', 'label' => 'Eager — Alerts you immediately for urgent emails'],
                ],
            ],
        ];
    }

    public function setupInstructions(): ?string
    {
        return <<<'MD'
        ## Getting Started

        The Email Management skill watches your inbox in the background and categorizes emails so you can get quick digests instead of scanning everything yourself.

        ### Connect your email
        1. Go to [Email Settings](/settings/email) to connect your email account via OAuth
        2. Once connected, the skill will begin categorizing emails automatically
        3. Ask **"give me an email digest"** to see what needs your attention

        ### How it works
        - Emails are sorted into categories: **action required**, **needs reply**, **financial**, **calendar**, **newsletters**, and more
        - In **observe mode**, the skill only reads and categorizes — it never modifies, moves, or deletes anything
        - You can change the operating mode and notification preferences above
        MD;
    }

    /**
     * @return array<int, array{label: string, status: string, message: string}>
     */
    public function statusDetails(): array
    {
        $configured = MailBridgeOAuthService::isConfigured();
        $triageEnabled = config('email-management.triage_enabled', true);
        $mode = config('email-management.operating_mode', 'observe');

        return [
            [
                'label' => 'Mail Bridge',
                'status' => $configured ? 'ok' : 'error',
                'message' => $configured
                    ? 'Connected and ready'
                    : 'Not configured — connect your email account in Email Settings',
            ],
            [
                'label' => 'Operating Mode',
                'status' => 'ok',
                'message' => ucfirst($mode),
            ],
            [
                'label' => 'Inbox Triage',
                'status' => $triageEnabled ? 'ok' : 'warning',
                'message' => $triageEnabled
                    ? 'Running every '.config('email-management.intervals.triage', 5).' minutes'
                    : 'Disabled',
            ],
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function buildModelOptions(): array
    {
        $options = [];
        $providers = config('agent.providers', []);

        foreach ($providers as $providerKey => $providerConfig) {
            if (! isset($providerConfig['models']) || ! is_array($providerConfig['models'])) {
                continue;
            }

            foreach ($providerConfig['models'] as $modelId => $modelName) {
                $options[] = [
                    'value' => "{$providerKey}:{$modelId}",
                    'label' => "{$modelName} ({$providerKey})",
                ];
            }
        }


        return $options;
    }
}
