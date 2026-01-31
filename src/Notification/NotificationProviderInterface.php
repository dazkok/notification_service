<?php

namespace App\Notification;

interface NotificationProviderInterface
{
    /**
     * @param array $content
     * @return bool
     */
    public function send(array $content): bool;

    /**
     * @param string $channel
     * @return bool
     */
    public function supports(string $channel): bool;
}
