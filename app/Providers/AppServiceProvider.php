<?php

namespace App\Providers;

use App\Models\Credential;
use App\Models\Ticket;
use App\Models\Workflow;
use App\Policies\CredentialPolicy;
use App\Policies\TicketPolicy;
use App\Policies\WorkflowPolicy;
use App\Subscriptions\SubscriptionManager;
use App\Tickets\TelegramTicketNotifier;
use App\Tickets\TicketManager;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SubscriptionManager::class);
        $this->app->singleton(TelegramTicketNotifier::class);
        $this->app->singleton(TicketManager::class);
    }

    public function boot(): void
    {
        Gate::policy(Credential::class, CredentialPolicy::class);
        Gate::policy(Workflow::class, WorkflowPolicy::class);
        Gate::policy(Ticket::class, TicketPolicy::class);
    }
}
