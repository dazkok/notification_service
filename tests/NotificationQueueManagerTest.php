<?php

namespace App\Tests;

use App\Dto\NotificationRequestDto;
use App\Entity\NotificationLog;
use App\Enum\NotificationChannel;
use App\Enum\NotificationStatus;
use App\Service\NotificationQueueManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

class NotificationQueueManagerTest extends DatabaseTestCase
{
    private EntityManagerInterface $entityManager;
    private NotificationQueueManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        
        $container = self::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->manager = $container->get(NotificationQueueManager::class);

        // Reset rate limiter for test users to ensure clean state
        $limiterFactory = $container->get('limiter.notification_api');
        $limiterFactory->create(1)->reset();  // For main test user
        $limiterFactory->create(99)->reset(); // For throttling test user
    }

    public function test_enqueue_create_db_log_and_dispatch_message(): void
    {
        $dto = new NotificationRequestDto(
            userId: 1,
            channels: [NotificationChannel::EMAIL->value, NotificationChannel::SMS->value],
            scheduledDate: null,
            content: [
                'subject' => 'Test subject',
                'body' => 'Test body',
                'email' => 'dazkok@gmail.com',
                'phone' => '+48733985973'
            ]
        );

        $this->manager->enqueue($dto);

        $repo = $this->entityManager->getRepository(NotificationLog::class);
        $logs = $repo->findBy(['user_id' => 1]);

        $this->assertCount(2, $logs, 'Should create 2 logs (email and sms)');

        $emailLog = $repo->findOneBy(['type' => NotificationChannel::EMAIL->value, 'user_id' => 1]);
        $this->assertEquals(NotificationStatus::PENDING, $emailLog->getStatus());
        $this->assertEquals($dto->content['email'], $emailLog->getRecipient());

        $phoneLog = $repo->findOneBy(['type' => NotificationChannel::SMS->value, 'user_id' => 1]);
        $this->assertEquals(NotificationStatus::PENDING, $phoneLog->getStatus());
        $this->assertEquals($dto->content['phone'], $phoneLog->getRecipient());

        $this->assertQueuedMessagesCount(count($dto->channels));
    }

    private function assertQueuedMessagesCount(int $count): void
    {
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.async');
        $this->assertCount($count, $transport->getSent());
    }

    public function test_enqueue_with_scheduled_date_adds_delay_stamp(): void
    {
        $futureDate = new \DateTimeImmutable('+1 hour');
        $dto = new NotificationRequestDto(1, ['email'], $futureDate, ['email' => 'test@test.com']);

        $this->manager->enqueue($dto);

        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.async');
        $envelopes = $transport->getSent();

        $stamp = $envelopes[0]->last(\Symfony\Component\Messenger\Stamp\DelayStamp::class);

        $this->assertNotNull($stamp, 'DelayStamp should be present');
        $this->assertGreaterThan(0, $stamp->getDelay());
    }

    public function test_enqueue_fails_when_throttling_limit_reached(): void
    {
        $userId = 99;

        $limit = self::getContainer()->getParameter('notification_rate_limit');

        $dto = new NotificationRequestDto(
            userId: $userId,
            channels: [NotificationChannel::EMAIL->value],
            scheduledDate: null,
            content: ['email' => 'test@test.com']
        );

        // Enqueue exactly the limit number of notifications - these should succeed
        for ($i = 0; $i < $limit; $i++) {
            $this->manager->enqueue($dto);
        }

        // The next one should fail due to throttling
        $this->expectException(\App\Exception\ThrottlingException::class);
        $this->expectExceptionMessage("Limit reached for user $userId");

        $this->manager->enqueue($dto);
    }
}
