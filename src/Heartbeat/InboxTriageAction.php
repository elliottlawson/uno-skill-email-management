<?php

declare(strict_types=1);

namespace ElliottLawson\EmailManagement\Heartbeat;

use App\Heartbeat\Contracts\HeartbeatAction;
use App\Heartbeat\HeartbeatContext;
use App\Services\MailBridgeOAuthService;
use ElliottLawson\EmailManagement\Services\MailBridgeClient;
use ElliottLawson\EmailManagement\Services\TriageService;

class InboxTriageAction implements HeartbeatAction
{
    public function __construct(
        private TriageService $triageService,
        private MailBridgeClient $mailBridgeClient,
    ) {}

    public function key(): string
    {
        return 'email-inbox-triage';
    }

    public function name(): string
    {
        return 'Email Inbox Triage';
    }

    public function description(): string
    {
        return 'Categorizes new emails in connected mailboxes using LLM analysis.';
    }

    public function intervalMinutes(): int
    {
        return (int) config('email-management.intervals.triage', 5);
    }

    public function isEnabled(): bool
    {
        return (bool) config('email-management.triage_enabled', true);
    }

    public function shouldRun(): bool
    {
        return MailBridgeOAuthService::isConfigured();
    }

    public function run(HeartbeatContext $context): void
    {
        $userIds = $this->mailBridgeClient->getConnectedUserIds();

        if (empty($userIds)) {
            $context->log('InboxTriage: No users with Mail Bridge connections.');

            return;
        }

        foreach ($userIds as $userId) {
            try {
                $result = $this->triageService->triageUser($userId);
                $context->log("InboxTriage: User {$userId} — {$result->summary()}");
            } catch (\Throwable $e) {
                $context->log(
                    "InboxTriage: Failed for user {$userId} — {$e->getMessage()}",
                    'error'
                );
            }
        }
    }
}
