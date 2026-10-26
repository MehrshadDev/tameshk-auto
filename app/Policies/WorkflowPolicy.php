<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workflow;
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
        if ($user->workflows()->count() >= config('workflow.max_per_user')) {
            return Response::deny('به سقف تعداد ورکفلو رسیدی. برای ساخت ورکفلو جدید یکی از قبلی‌ها رو حذف کن.');
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
