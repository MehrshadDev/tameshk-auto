<?php

namespace App\Tickets;

use App\Models\Ticket;
use App\Subscriptions\SubscriptionManager;
use App\Workflow\Support\DateTimeHelper;
use App\Workflow\Support\TelegramApi;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class TelegramTicketNotifier
{
    public function sendNewTicketAlert(Ticket $ticket): void
    {
        $botToken = trim((string) config('tickets.telegram.bot_token', ''));
        $chatId = trim((string) config('tickets.telegram.chat_id', ''));

        if ($botToken === '' || $chatId === '') {
            return;
        }

        $ticket->loadMissing(['user', 'latestMessage']);

        $endpoint = TelegramApi::url($botToken, 'sendMessage');
        $text = $this->formatMessage($ticket);

        try {
            $response = Http::timeout(15)->post($endpoint, [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);

            if ($response->failed()) {
                Log::warning('Telegram ticket notification failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function formatMessage(Ticket $ticket): string
    {
        $createdAt = ($ticket->created_at ?? now())->copy()->setTimezone('Asia/Tehran');
        $persianDate = DateTimeHelper::toPersianDate($createdAt, 'yyyy/MM/dd - HH:mm');

        $planName = $ticket->user
            ? app(SubscriptionManager::class)->resolvePlan($ticket->user)->name
            : 'رایگان';

        $userName = $this->escape($ticket->user?->name ?? 'کاربر');
        $userEmail = $this->escape($ticket->user?->email ?? '—');
        $escapedPlan = $this->escape($planName);
        $subject = $this->escape($ticket->subject);
        $department = $this->escape($ticket->department->label());
        $priority = $this->escape($ticket->priority->label());
        $bodyPreview = $this->escape(Str::limit((string) ($ticket->latestMessage?->body ?? ''), 500));
        $attachmentsCount = is_array($ticket->latestMessage?->attachments) ? count($ticket->latestMessage->attachments) : 0;

        $rawUrl = route('app.tickets.show', ['ticket' => $ticket->public_id]);
        $escapedUrl = $this->escape($rawUrl);
        $host = (string) parse_url($rawUrl, PHP_URL_HOST);
        $isPublicUrl = $host !== '' && ! in_array($host, ['localhost', '127.0.0.1'], true) && str_contains($host, '.');

        $linkLine = $isPublicUrl
            ? "🔗 <a href=\"$escapedUrl\">مشاهده و پاسخ به تیکت</a>"
            : "🔗 <b>لینک تیکت:</b> <code>$escapedUrl</code>";

        $priorityIcon = match ($ticket->priority->value) {
            'high' => '🔴',
            'medium' => '🟡',
            default => '🟢',
        };

        $lines = [
            "🎫 <b>تیکت جدید ثبت شد (#$ticket->id)</b>",
            "",
            "📌 <b>عنوان:</b> $subject",
            "🏢 <b>بخش:</b> $department",
            "$priorityIcon <b>اولویت:</b> $priority",
            "👤 <b>کاربر:</b> $userName (<code>$userEmail</code>)",
            "👑 <b>پلن اشتراک:</b> $escapedPlan",
            "🕒 <b>زمان ایجاد:</b> $persianDate",
        ];

        if ($attachmentsCount > 0) {
            $lines[] = "🖼 <b>تصاویر پیوست:</b> $attachmentsCount فایل";
        }

        array_push(
            $lines,
            "",
            "💬 <b>متن پیام:</b>",
            "<blockquote>$bodyPreview</blockquote>",
            "",
            $linkLine,
        );

        return implode("\n", $lines);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
