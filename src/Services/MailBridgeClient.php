<?php

declare(strict_types=1);

namespace ElliottLawson\EmailManagement\Services;

use App\Models\UserExternalIdentity;
use App\Services\MailBridgeOAuthService;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Tool as PrismTool;
use Prism\Relay\Relay;

/**
 * Client for interacting with the Mail Bridge MCP server via Prism Relay.
 *
 * Loads Relay tools at runtime, finds the named tool, and calls its handler.
 */
class MailBridgeClient
{
    public function __construct(
        private MailBridgeOAuthService $oauthService,
    ) {}

    /**
     * List all mailboxes for the given user.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listMailboxes(int $userId): array
    {
        return $this->callTool($userId, 'list-mailboxes', []);
    }

    /**
     * List messages in a mailbox, optionally filtered.
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function listMessages(int $userId, string $mailboxId, array $filters = []): array
    {
        $params = array_merge(['mailboxId' => $mailboxId], $filters);

        return $this->callTool($userId, 'list-messages', $params);
    }

    /**
     * Get a single message by ID.
     *
     * @return array<string, mixed>|null
     */
    public function getMessage(int $userId, string $mailboxId, string $messageId): ?array
    {
        $result = $this->callTool($userId, 'get-message', [
            'mailboxId' => $mailboxId,
            'messageId' => $messageId,
        ]);

        return $result ?: null;
    }

    /**
     * Get all user IDs that have a Mail Bridge connection.
     *
     * @return array<int, int>
     */
    public function getConnectedUserIds(): array
    {
        return UserExternalIdentity::query()
            ->where('provider', 'mail-bridge')
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Call a Mail Bridge MCP tool via Relay.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function callTool(int $userId, string $toolName, array $params): array
    {
        $token = $this->oauthService->getAccessTokenForUserId($userId);

        if (! $token) {
            Log::warning('MailBridgeClient: No access token for user', ['user_id' => $userId]);

            return [];
        }

        $this->configureRelayUrl();

        try {
            $tools = (new Relay('mail-bridge'))->withToken($token)->tools();
            $relayToolName = "relay__mail-bridge__{$toolName}";

            $tool = collect($tools)->first(fn (PrismTool $t) => $t->name() === $relayToolName);

            if (! $tool) {
                Log::warning('MailBridgeClient: Tool not found', ['tool' => $toolName]);

                return [];
            }

            $result = $tool->handle(...$params);
            $decoded = json_decode((string) $result, true);

            return is_array($decoded) ? $decoded : [];
        } catch (\Throwable $e) {
            Log::error('MailBridgeClient: Tool call failed', [
                'tool' => $toolName,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Set the Relay server URL from system settings at runtime.
     */
    private function configureRelayUrl(): void
    {
        $tokenEndpoint = MailBridgeOAuthService::getTokenEndpoint();

        if ($tokenEndpoint) {
            $mcpUrl = str_replace('/connect/token', '/mcp', $tokenEndpoint);
            config(['relay.servers.mail-bridge.url' => $mcpUrl]);
        }
    }
}
