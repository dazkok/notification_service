<?php

namespace App\Enum;

enum NotificationChannel: string
{
    case EMAIL = 'email';
    case SMS = 'sms';
    case PUSH = 'push';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
