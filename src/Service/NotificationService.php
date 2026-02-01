<?php

namespace App\Service;

use App\Entity\NotificationLog;
use App\Enum\NotificationChannel;
use App\Notification\HtmlCapableInterface;
use App\Notification\NotificationProviderInterface;
use App\Notification\NotificationRenderer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class NotificationService
{
    /**
     * @param iterable<NotificationProviderInterface> $providers
     * @param LoggerInterface $logger
     * @param NotificationRenderer $renderer
     * @param string $defaultTemplate
     */
    public function __construct(
        /**
         * @var iterable<NotificationProviderInterface>
         */
        private readonly iterable             $providers,
        private readonly LoggerInterface      $logger,
        private readonly NotificationRenderer $renderer,
        private readonly string               $defaultTemplate
    )
    {
    }

    /**
     * @param NotificationLog $log
     * @return void
     * @throws TransportExceptionInterface
     */
    public function process(NotificationLog $log): void
    {
        $channel = $log->getType();
        $content = $log->getContent();

        $preparedContent = $this->prepareContentForChannel($channel, $content);

        if ($channel === NotificationChannel::EMAIL->value) {
            $preparedContent['email'] = $log->getRecipient();
        }

        $this->sendNotification($channel, $preparedContent);
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

    /**
     * @param string $channel
     * @param array $content
     * @return array
     */
    private function prepareContentForChannel(string $channel, array $content): array
    {
        $needsHtml = false;
        foreach ($this->providers as $provider) {
            if ($provider->supports($channel) && $provider instanceof HtmlCapableInterface) {
                $needsHtml = true;
                break;
            }
        }

        if ($needsHtml) {
            $template = $content['template'] ?? $this->defaultTemplate;
            try {
                $content['html'] = $this->renderer->render($template, $content);
            } catch (\Exception $e) {
                $this->logger->error('Template rendering failed: ' . $e->getMessage());
            }
        }

        return $content;
    }
}
