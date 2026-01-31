<?php

namespace App\Service;

use App\Dto\NotificationRequestDto;
use App\Notification\HtmlCapableInterface;
use App\Notification\NotificationProviderInterface;
use App\Notification\NotificationRenderer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class NotificationService
{
    public function __construct(
        /**
         * @var iterable<NotificationProviderInterface>
         */
        private readonly iterable        $providers,
        private readonly LoggerInterface $logger,
        private readonly NotificationRenderer $renderer,
        private readonly string $defaultTemplate
    )
    {
    }

    /**
     * @param NotificationRequestDto $dto
     * @return void
     * @throws TransportExceptionInterface
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
     * @throws TransportExceptionInterface
     */
    private function sendNotification(string $channel, array $content): void
    {
        $executed = false;
        $htmlRendered = false;

        foreach ($this->providers as $provider) {
            if (!$provider->supports($channel)) {
                continue;
            }

            $executed = true;

            if ($provider instanceof HtmlCapableInterface && !$htmlRendered) {
                $template = $content['template'] ?? $this->defaultTemplate;

                try {
                    $content['html'] = $this->renderer->render($template, $content);
                    $htmlRendered = true;
                } catch (\Exception $e) {
                    $this->logger->error('Template rendering failed: ' . $e->getMessage());
                }
            }

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
