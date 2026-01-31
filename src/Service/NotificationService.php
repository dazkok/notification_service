<?php

namespace App\Service;

use App\Dto\NotificationRequestDto;
use App\Notification\NotificationProviderInterface;
use Psr\Log\LoggerInterface;

class NotificationService
{
    public function __construct(
        /**
         * @var iterable<NotificationProviderInterface>
         */
        private readonly iterable        $providers,
        private readonly LoggerInterface $logger,
    )
    {
    }

    /**
     * @param NotificationRequestDto $dto
     * @return void
     */
    public function process(NotificationRequestDto $dto): void
    {
        foreach ($dto->channels as $channel) {
            $this->sendNotification($channel, $dto->content);
        }
    }

    /**
     * @param string $channel
     * @param array $content
     * @return void
     */
    private function sendNotification(string $channel, array $content): void
    {
        $executed = false;

        foreach ($this->providers as $provider) {
            if (!$provider->supports($channel)) {
                continue;
            }

            $executed = true;

            try {
                if ($provider->send($content)) {
                    return;
                }
            } catch (\Exception $e) {
                $this->logger->error(sprintf(
                    'Provider %s failed for channel %s: %s',
                    get_class($provider), $channel, $e->getMessage()
                ));
            }
        }

        if (!$executed) {
            throw new \RuntimeException('No providers registered for channel: ' . $channel);
        }

        throw new \RuntimeException("All providers for channel '$channel' failed.");
    }
}
