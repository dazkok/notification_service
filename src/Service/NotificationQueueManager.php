<?php

namespace App\Service;

use App\Dto\NotificationRequestDto;
use App\Entity\NotificationLog;
use App\Enum\NotificationChannel;
use App\Exception\ThrottlingException;
use App\Message\SendNotification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\RateLimiter\RateLimiterFactory;

readonly class NotificationQueueManager
{
    /**
     * @param EntityManagerInterface $entityManager
     * @param MessageBusInterface $bus
     * @param RateLimiterFactory $notificationApiLimiter
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface    $bus,
        private RateLimiterFactory     $notificationApiLimiter,
    )
    {
    }

    /**
     * @param NotificationRequestDto $dto
     * @return void
     */
    public function enqueue(NotificationRequestDto $dto): void
    {
        $limiter = $this->notificationApiLimiter->create($dto->userId);
        if (false === $limiter->consume()->isAccepted()) {
            throw new ThrottlingException("Limit reached for user {$dto->userId}");
        }

        $this->entityManager->wrapInTransaction(function () use ($dto) {
            foreach ($dto->channels as $channel) {
                $log = $this->createLog($dto, $channel);

                $this->entityManager->persist($log);
                $this->entityManager->flush();

                $delay = $this->calculateDelay($dto->scheduledDate);
                $this->bus->dispatch(
                    new SendNotification($log->getId()),
                    $delay > 0 ? [new DelayStamp($delay)] : []
                );
            }
        });
    }

    /**
     * @param NotificationRequestDto $dto
     * @param string $channel
     * @return NotificationLog
     */
    private function createLog(NotificationRequestDto $dto, string $channel): NotificationLog
    {
        $log = new NotificationLog();
        $log->setUserId($dto->userId);
        $log->setType($channel);

        $channelEnum = NotificationChannel::tryFrom($channel);
        $requiredField = $channelEnum->getRequiredField();

        $recipient = ($requiredField && isset($dto->content[$requiredField]))
            ? $dto->content[$requiredField]
            : $dto->userId;

        $log->setRecipient($recipient);
        $log->setContent($dto->content);
        $log->setScheduledAt($dto->scheduledDate);
        return $log;
    }

    /**
     * @param ?\DateTimeImmutable $scheduledDate
     * @return int
     */
    private function calculateDelay(?\DateTimeImmutable $scheduledDate): int
    {
        if (!$scheduledDate) return 0;
        $diff = $scheduledDate->getTimestamp() - time();
        return $diff > 0 ? $diff * 1000 : 0;
    }
}
