<?php

namespace App\Service;

use App\Dto\NotificationRequestDto;
use App\Notification\NotificationProviderInterface;
use Psr\Log\LoggerInterface;

class NotificationService
{
    public function __construct(
        /**
         * @var NotificationProviderInterface[]
         */
        private iterable        $providers,
        private LoggerInterface $logger,
    )
    {
    }

    public function process(NotificationRequestDto $dto): void
    {
        foreach ($dto->channels as $channel) {
            $this->sendNotification($channel, $dto->content);
        }
    }

    private function sendNotification(string $channel, array $content): void
    {
        $sent = false;

        foreach ($this->providers as $provider) {
            if (!$provider->supports($channel)) {
                continue;
            }

            try {
                if ($provider->send($content)) {
                    $sent = true;
                    break;
                }
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }

        if (!$sent) {
            throw new \RuntimeException('All providers failed to send notification. Channel: ' . $channel);
        }
    }
}
