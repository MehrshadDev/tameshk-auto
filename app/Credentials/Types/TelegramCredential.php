<?php

namespace App\Credentials\Types;

use App\Credentials\Contracts\CredentialTypeContract;
use App\Credentials\DTOs\CredentialDefinition;
use App\Credentials\Exceptions\InvalidCredentialException;

class TelegramCredential implements CredentialTypeContract
{
    public static function key(): string
    {
        return 'telegram';
    }

    public static function definition(): CredentialDefinition
    {
        return new CredentialDefinition(
            key: 'telegram',
            name: 'ربات تلگرام',
            description: 'توکن اختصاصی ربات تلگرام دریافتی از BotFather@',
            icon: 'brand:telegram',
            schema: [
                'bot_token' => [
                    'type'     => 'password',
                    'label'    => 'توکن ربات (Bot Token)',
                    'required' => true,
                    'placeholder' => '123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ',
                    'help'     => 'توکن ربات خود را از طریق گفت‌وگو با BotFather@ دریافت کنید.',
                ],
            ],
        );
    }

    public function validate(array $data): void
    {
        if (empty($data['bot_token']) || ! is_string($data['bot_token'])) {
            throw InvalidCredentialException::because('توکن ربات تلگرام الزامی است.');
        }

        $token = trim($data['bot_token']);

        if (! preg_match('/^\d+:[\w-]{30,}$/', $token)) {
            throw InvalidCredentialException::because('فرمت توکن ربات تلگرام واردشده نامعتبر است.');
        }
    }
}
