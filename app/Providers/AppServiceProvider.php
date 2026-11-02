<?php

namespace App\Providers;

use App\Models\Credential;
use App\Policies\CredentialPolicy;
use App\Subscriptions\SubscriptionManager;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SubscriptionManager::class);
    }

    public function boot(): void
    {
        Gate::policy(Credential::class, CredentialPolicy::class);
    }
}
