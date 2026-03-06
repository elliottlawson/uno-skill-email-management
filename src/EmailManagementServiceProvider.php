<?php

declare(strict_types=1);

namespace ElliottLawson\EmailManagement;

use App\Services\SkillDatabaseManager;
use App\Services\SkillRegistry;
use Illuminate\Support\ServiceProvider;

class EmailManagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/email-management.php',
            'email-management'
        );
    }

    public function boot(): void
    {
        $this->app->make(SkillDatabaseManager::class)
            ->connectionFor('email-management');

        $this->app->make(SkillRegistry::class)
            ->register(new EmailManagementSkill);
    }
}
