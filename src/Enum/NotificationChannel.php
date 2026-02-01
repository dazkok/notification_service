<?php

namespace App\Enum;

enum NotificationChannel: string
{
    case EMAIL = 'email';
    case SMS = 'sms';
    case PUSH = 'push';

    public function getRequiredField(): ?string
    {
        return match ($this) {
            self::EMAIL => 'email',
            self::SMS => 'phone',
            self::PUSH => null,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, self::values(), true);
    }
}
