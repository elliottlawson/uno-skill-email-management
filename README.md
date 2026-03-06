# Email Management Skill for Uno

Intelligent email triage, attention surfacing, and anomaly detection — a higher-level intelligence layer that orchestrates on top of raw Mail Bridge MCP tools.

## What This Does

While the built-in Email skill provides raw mailbox access (list, read, send, reply), this skill adds:

- **Triage & Categorization** — Automatically categorize emails (action_required, needs_reply, informational, newsletter, etc.)
- **Attention Surfacing** — Identify high-priority items that need the user's attention
- **Digest Generation** — Summarized views of email activity across mailboxes
- **Anomaly Detection** — Flag unusual patterns (volume spikes, new senders for sensitive topics)
- **User-Configurable Rules** — Custom triage rules for personalized categorization

## Requirements

- [Uno Bot](https://github.com/elliottlawson/bot) with the pluggable skill system
- Mail Bridge configured and connected (the base Email skill must be working)

## Installation

In the Uno settings UI:

1. Go to **Settings → Skills**
2. Paste `https://github.com/elliottlawson/uno-skill-email-management`
3. Click **Install**

Or via CLI:

```bash
composer require elliottlawson/uno-skill-email-management
```

## Configuration

The skill uses sensible defaults. Override via environment variables:

```env
# Processing intervals (minutes)
EMAIL_TRIAGE_INTERVAL=5
EMAIL_ATTENTION_INTERVAL=15
EMAIL_ANOMALY_INTERVAL=60

# LLM model for categorization (provider:model format)
EMAIL_CATEGORIZATION_MODEL=anthropic:claude-haiku-4-5-20251001
```

Full config can be published:

```bash
php artisan vendor:publish --tag=email-management-config
```

## Current Status

**v1.0.0** — System prompt only. The intelligence tools and background processing will be added incrementally. The skill currently:

- Injects rich context about email management capabilities into the agent's system prompt
- Provides database schema ready for the intelligence layer (processing cursors, categorized messages, attention items, triage rules)
- Checks that Mail Bridge is configured before loading

## Architecture

```
src/
├── EmailManagementSkill.php          # Skill contract implementation
├── EmailManagementServiceProvider.php # Laravel service provider
├── Models/
│   ├── ProcessingCursor.php          # Per-mailbox processing state
│   ├── CategorizedMessage.php        # Categorized email records
│   ├── AttentionItem.php             # Surfaced attention items
│   └── TriageRule.php                # User categorization rules
└── Services/
    └── MailBridgeClient.php          # Relay client stub
```

Data is stored in an isolated SQLite database at `storage/skills/email-management/database.sqlite`.

## License

MIT
