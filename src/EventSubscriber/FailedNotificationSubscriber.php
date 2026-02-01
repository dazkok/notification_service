<?php

namespace App\EventSubscriber;

use App\Enum\NotificationStatus;
use App\Message\SendNotification;
use App\Repository\NotificationLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;

final readonly class FailedNotificationSubscriber implements EventSubscriberInterface
{
    /**
     * @param NotificationLogRepository $repository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        private NotificationLogRepository $repository,
        private EntityManagerInterface    $entityManager
    )
    {
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageFailedEvent::class => 'onMessageFailed',
        ];
    }

    /**
     * @param WorkerMessageFailedEvent $event
     * @return void
     */
    public function onMessageFailed(WorkerMessageFailedEvent $event): void
    {
        $envelope = $event->getEnvelope();
        $message = $envelope->getMessage();

        if (!$message instanceof SendNotification) {
            return;
        }

        if (!$event->willRetry()) {
            $log = $this->repository->find($message->notificationLogId);

            if ($log) {
                $log->setStatus(NotificationStatus::FAILED);
                $this->entityManager->flush();
            }
        }
    }
}
