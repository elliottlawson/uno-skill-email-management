<?php

declare(strict_types=1);

namespace ElliottLawson\EmailManagement;

use App\Contracts\Skill;
use App\Enums\DangerLevel;
use App\Services\MailBridgeOAuthService;

class EmailManagementSkill implements Skill
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
        // TODO: Add intelligence layer tools that query the local SQLite DB
        // - get_email_digest: categorized summary of recent mail
        // - get_attention_items: items needing user attention
        // - categorize_mailbox: trigger on-demand triage
        // - manage_triage_rules: CRUD for user categorization rules
        return [];
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
        return <<<'PROMPT'
## Email Management Intelligence

You have access to an intelligent email management layer that goes beyond raw mailbox access. This system provides:

### Capabilities
- **Triage & Categorization**: Emails are automatically categorized (action_required, needs_reply, informational, newsletter, notification, spam, personal, financial, calendar)
- **Attention Surfacing**: High-priority items that need the user's attention are identified and surfaced with reasons
- **Digest Generation**: Summarized views of email activity across all mailboxes
- **Anomaly Detection**: Unusual patterns (volume spikes, new senders for sensitive topics, etc.) are flagged

### How to Use
When the user asks about email triage, digests, priorities, or attention items, prefer the intelligence tools (when available) over browsing raw mail. The intelligence layer has already processed and categorized messages, so it can answer questions like:
- "What needs my attention?" → Use attention items
- "Give me a digest" → Use categorized summary
- "Any unusual email activity?" → Use anomaly detection
- "Categorize my inbox" → Trigger on-demand triage

For raw email operations (reading specific messages, sending, replying), the base Email skill's tools are more appropriate.

### Current Status
The intelligence layer is being built incrementally. If intelligence tools are not yet available, fall back to the raw Email skill tools and apply your own judgment for triage and prioritization.
PROMPT;
    }
}
