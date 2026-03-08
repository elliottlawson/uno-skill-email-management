<?php

declare(strict_types=1);

namespace ElliottLawson\EmailManagement;

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
        \Log::info('EmailManagement: boot() called');

        $this->app->make(SkillRegistry::class)
            ->register(new EmailManagementSkill);
    }
}
