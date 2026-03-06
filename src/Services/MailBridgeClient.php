<?php

declare(strict_types=1);

namespace ElliottLawson\EmailManagement\Services;

/**
 * Client for interacting with the Mail Bridge MCP server via Prism Relay.
 *
 * This is a stub — methods will be implemented as the intelligence layer is built.
 * The pattern for Relay calls:
 *   1. Resolve the Mail Bridge URL from SystemSetting::get('mail_bridge_url')
 *   2. Set config(['relay.servers.mail-bridge.url' => $url]) at runtime
 *   3. Get OAuth token via MailBridgeOAuthService::getAccessTokenForUserId()
 *   4. Call Relay::withToken($token)->call('mail-bridge', $toolName, $params)
 */
class MailBridgeClient
{
    // TODO: Inject MailBridgeOAuthService and resolve user context

    /**
     * List all mailboxes for the current user.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listMailboxes(int $userId): array
    {
        // TODO: Call Relay 'list-mailboxes' tool
        return [];
    }

    /**
     * List messages in a mailbox, optionally filtered.
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function listMessages(int $userId, string $mailboxId, array $filters = []): array
    {
        // TODO: Call Relay 'list-messages' tool with filters
        return [];
    }

    /**
     * Get a single message by ID.
     *
     * @return array<string, mixed>|null
     */
    public function getMessage(int $userId, string $mailboxId, string $messageId): ?array
    {
        // TODO: Call Relay 'get-message' tool
        return null;
    }
}
