<?php

namespace App\MessageHandler;

use App\Message\SendNotification;
use App\Service\NotificationService;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SendNotificationHandler
{
    public function __construct(
        private NotificationService $notificationService
    )
    {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function __invoke(SendNotification $message): void
    {
        $this->notificationService->process($message->dto);
    }
}
