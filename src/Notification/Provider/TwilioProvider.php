<?php

namespace App\Notification\Provider;

use App\Enum\NotificationChannel;
use App\Notification\NotificationProviderInterface;

class TwilioProvider implements NotificationProviderInterface
{
    public function send(array $content): bool
    {
        // Twilio send logic

        return true;
    }

    public function supports(string $channel): bool
    {
        return $channel === NotificationChannel::SMS->value;
    }
}
