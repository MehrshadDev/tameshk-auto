<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workflow;
use App\Subscriptions\SubscriptionManager;
use Illuminate\Auth\Access\Response;

class WorkflowPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Workflow $workflow): bool
    {
        return $workflow->user_id === $user->id;
    }

    public function create(User $user): Response
    {
        $max = app(SubscriptionManager::class)->maxTotalWorkflows($user);

        if ($max !== null && $user->workflows()->count() >= $max) {
            return Response::deny('به سقف تعداد ورکفلو رسیدی. برای ساخت ورکفلو جدید یکی از قبلی‌ها رو حذف کن یا اشتراکت رو ارتقا بده.');
        }

        return Response::allow();
    }

    public function update(User $user, Workflow $workflow): bool
    {
        return $workflow->user_id === $user->id;
    }

    public function delete(User $user, Workflow $workflow): bool
    {
        return $workflow->user_id === $user->id;
    }
}
