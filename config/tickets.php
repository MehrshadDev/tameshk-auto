<?php

return [
    'admin_emails' => array_values(array_filter(array_map(
        fn (string $email) => strtolower(trim($email)),
        explode(',', (string) env('SUPPORT_ADMIN_EMAILS', ''))
    ))),

    'telegram' => [
        'bot_token' => env('SUPPORT_TELEGRAM_BOT_TOKEN', ''),
        'chat_id' => env('SUPPORT_TELEGRAM_CHAT_ID', ''),
    ],

    'attachments_disk' => env('SUPPORT_ATTACHMENTS_DISK', 'public'),

    'max_open_per_user' => (int) env('SUPPORT_MAX_OPEN_TICKETS', 10),

    'max_consecutive_user_replies' => (int) env('SUPPORT_MAX_CONSECUTIVE_REPLIES', 4),

    'create_cooldown_seconds' => (int) env('SUPPORT_CREATE_COOLDOWN_SECONDS', 60),

    'reply_cooldown_seconds' => (int) env('SUPPORT_REPLY_COOLDOWN_SECONDS', 10),
];
