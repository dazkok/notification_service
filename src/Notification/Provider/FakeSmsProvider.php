<?php

namespace App\Notification\Provider;

use App\Enum\NotificationChannel;
use App\Notification\NotificationProviderInterface;

class FakeSmsProvider implements NotificationProviderInterface
{
    public function send(array $content): bool
    {
        // always return success (fake)

        return true;
    }

    public function supports(string $channel): bool
    {
        return $channel === NotificationChannel::SMS->value;
    }
}
