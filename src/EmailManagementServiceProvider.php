<?php

declare(strict_types=1);

namespace ElliottLawson\EmailManagement;

use App\Heartbeat\HeartbeatActionRegistry;
use App\Services\SkillDatabaseManager;
use App\Services\SkillRegistry;
use ElliottLawson\EmailManagement\Heartbeat\InboxTriageAction;
use ElliottLawson\EmailManagement\Services\MailBridgeClient;
use ElliottLawson\EmailManagement\Services\TriageService;
use Illuminate\Support\ServiceProvider;

class EmailManagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/email-management.php',
            'email-management'
        );

        $this->app->singleton(MailBridgeClient::class);
        $this->app->singleton(TriageService::class);
    }

    public function boot(): void
    {
        try {
            $dbManager = $this->app->make(SkillDatabaseManager::class);
            $dbManager->connectionFor('email-management');
            $dbManager->migrate('email-management', __DIR__.'/../database/migrations');
        } catch (\Throwable $e) {
            \Log::error('EmailManagement: DB setup failed', ['error' => $e->getMessage()]);
        }

        try {
            $this->app->make(SkillRegistry::class)
                ->register($this->app->make(EmailManagementSkill::class));
        } catch (\Throwable $e) {
            \Log::error('EmailManagement: Skill registration failed', ['error' => $e->getMessage()]);
        }

        try {
            $this->app->make(HeartbeatActionRegistry::class)
                ->register($this->app->make(InboxTriageAction::class));
        } catch (\Throwable $e) {
            \Log::error('EmailManagement: Heartbeat registration failed', ['error' => $e->getMessage()]);
        }
    }
}
