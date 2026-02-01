<?php

namespace App\MessageHandler;

use App\Enum\NotificationStatus;
use App\Message\SendNotification;
use App\Repository\NotificationLogRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SendNotificationHandler
{
    /**
     * @param NotificationLogRepository $repository
     * @param NotificationService $notificationService
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        private NotificationLogRepository $repository,
        private NotificationService       $notificationService,
        private EntityManagerInterface    $entityManager
    )
    {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function __invoke(SendNotification $message): void
    {
        $log = $this->repository->find($message->notificationLogId);

        if (!$log) {
            return;
        }

        //in case of failure, the message will be retried (see config/packages/messenger.yaml)
        $this->notificationService->process($log);

        $log->setSentAt(new \DateTimeImmutable());
        $log->setStatus(NotificationStatus::SENT);
        $this->entityManager->flush();
    }
}
