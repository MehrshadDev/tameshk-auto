<?php

namespace App\Tickets;

use App\Jobs\SendAdminTicketAlertEmailJob;
use App\Jobs\SendTicketReplyEmailJob;
use App\Jobs\SendTicketTelegramNotificationJob;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Subscriptions\SubscriptionManager;
use App\Tickets\Enums\TicketDepartment;
use App\Tickets\Enums\TicketPriority;
use App\Tickets\Enums\TicketStatus;
use App\Tickets\Exceptions\TicketException;
use App\Tickets\Support\TicketAttachmentStorage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class TicketManager
{
    public function create(
        User $user,
        string $subject,
        string $body,
        TicketDepartment $department,
        TicketPriority $priority = TicketPriority::Medium,
        array $attachments = [],
    ): Ticket {
        if ($user->isAdmin()) {
            throw TicketException::because('حساب‌های مدیریتی مجاز به ثبت تیکت جدید نیستند.');
        }

        $maxOpen = max(1, (int) config('tickets.max_open_per_user', 10));
        $cooldown = max(0, (int) config('tickets.create_cooldown_seconds', 10));

        $ticket = DB::transaction(function () use ($user, $subject, $body, $department, $priority, $attachments, $maxOpen, $cooldown): Ticket {
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($cooldown > 0) {
                $lastCreatedAt = $lockedUser->tickets()->latest('id')->value('created_at');

                if ($lastCreatedAt) {
                    $elapsed = (int) abs(now()->diffInSeconds(Carbon::parse($lastCreatedAt)));
                    if ($elapsed < $cooldown) {
                        $wait = max(1, $cooldown - $elapsed);
                        throw TicketException::because("لطفاً $wait ثانیه دیگر برای ثبت تیکت جدید صبر کنید.");
                    }
                }
            }

            $openCount = $lockedUser->tickets()
                ->where('status', '!=', TicketStatus::Closed->value)
                ->count();

            if ($openCount >= $maxOpen) {
                throw TicketException::because(
                    "شما هم‌اکنون $maxOpen تیکت باز دارید. لطفاً تا بررسی تیکت‌های قبلی صبر کنید یا تیکت‌های حل‌شده را ببندید."
                );
            }

            $ticket = $lockedUser->tickets()->create([
                'subject' => $this->cleanText($subject),
                'department' => $department,
                'priority' => $priority,
                'status' => TicketStatus::Open,
                'last_reply_at' => now(),
            ]);

            $storedPaths = $attachments !== []
                ? TicketAttachmentStorage::storeMany($ticket->id, $attachments)
                : [];

            $ticket->messages()->create([
                'user_id' => $lockedUser->id,
                'is_admin_reply' => false,
                'body' => $this->cleanText($body),
                'attachments' => $storedPaths !== [] ? $storedPaths : null,
            ]);

            return $ticket;
        });

        SendTicketTelegramNotificationJob::dispatchSmart($ticket->id);

        $plan = app(SubscriptionManager::class)->resolvePlan($user);
        $shouldEmailAdmins = ! $plan->isFree()
            || ($department === TicketDepartment::Billing && $priority === TicketPriority::High);

        if ($shouldEmailAdmins) {
            SendAdminTicketAlertEmailJob::dispatchSmart($ticket->id);
        }

        return $ticket;
    }

    public function reply(Ticket $ticket, User $actor, string $body, array $attachments = []): TicketMessage
    {
        $shouldEmailUser = false;
        $maxConsecutive = max(1, (int) config('tickets.max_consecutive_user_replies', 5));
        $replyCooldown = max(0, (int) config('tickets.reply_cooldown_seconds', 5));

        $message = DB::transaction(function () use ($ticket, $actor, $body, $attachments, $maxConsecutive, $replyCooldown, &$shouldEmailUser): TicketMessage {
            $lockedTicket = Ticket::query()->whereKey($ticket->id)->lockForUpdate()->firstOrFail();

            if ($lockedTicket->isClosed() && ! $actor->isAdmin()) {
                throw TicketException::because('این تیکت بسته شده است. برای ادامه گفتگو ابتدا تیکت را بازگشایی کنید.');
            }

            $isAdminReply = $actor->isAdmin();
            $recentMessages = $lockedTicket->messages()->latest('id')->limit($maxConsecutive + 1)->get();
            $lastMessage = $recentMessages->first();

            if (! $isAdminReply) {
                if ($replyCooldown > 0 && $lastMessage && ! $lastMessage->is_admin_reply && $lastMessage->created_at) {
                    $elapsed = (int) abs(now()->diffInSeconds($lastMessage->created_at));
                    if ($elapsed < $replyCooldown) {
                        $wait = max(1, $replyCooldown - $elapsed);
                        throw TicketException::because("لطفاً $wait ثانیه صبر کنید و سپس پیام بعدی را بفرستید.");
                    }
                }

                $consecutiveUserMessages = $recentMessages
                    ->takeWhile(fn (TicketMessage $m) => ! $m->is_admin_reply)
                    ->count();

                if ($consecutiveUserMessages >= $maxConsecutive) {
                    throw TicketException::because(
                        "شما $maxConsecutive پیام متوالی ارسال کرده‌اید. لطفاً تا دریافت پاسخ کارشناسان پشتیبانی صبر کنید."
                    );
                }
            }

            $shouldEmailUser = $isAdminReply
                && $lockedTicket->user_id !== $actor->id
                && ($lastMessage === null || ! $lastMessage->is_admin_reply);

            $storedPaths = $attachments !== []
                ? TicketAttachmentStorage::storeMany($lockedTicket->id, $attachments)
                : [];

            $message = $lockedTicket->messages()->create([
                'user_id' => $actor->id,
                'is_admin_reply' => $isAdminReply,
                'body' => $this->cleanText($body),
                'attachments' => $storedPaths !== [] ? $storedPaths : null,
            ]);

            $nextStatus = $isAdminReply ? TicketStatus::Answered : TicketStatus::CustomerReply;

            $lockedTicket->update([
                'status' => $nextStatus,
                'last_reply_at' => now(),
                'closed_at' => null,
            ]);

            return $message;
        });

        if ($shouldEmailUser) {
            SendTicketReplyEmailJob::dispatchSmart($ticket->id);
        }

        return $message;
    }

    public function close(Ticket $ticket): void
    {
        if ($ticket->isClosed()) {
            return;
        }

        $ticket->update([
            'status' => TicketStatus::Closed,
            'closed_at' => now(),
        ]);
    }

    public function reopen(Ticket $ticket, User $actor): void
    {
        if (! $ticket->isClosed()) {
            return;
        }

        $ticket->update([
            'status' => $actor->isAdmin() ? TicketStatus::Answered : TicketStatus::CustomerReply,
            'closed_at' => null,
        ]);
    }

    public function delete(Ticket $ticket): void
    {
        DB::transaction(function () use ($ticket) {
            $lockedTicket = Ticket::query()->whereKey($ticket->id)->lockForUpdate()->first();

            if (! $lockedTicket) {
                return;
            }

            TicketAttachmentStorage::deleteForTicket($lockedTicket->id);
            $lockedTicket->delete();
        });
    }

    private function cleanText(string $text): string
    {
        $cleaned = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? $text;

        return trim($cleaned);
    }
}
