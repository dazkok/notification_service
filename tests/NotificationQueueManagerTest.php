<?php

namespace App\Tests;

use App\Dto\NotificationRequestDto;
use App\Entity\NotificationLog;
use App\Enum\NotificationChannel;
use App\Enum\NotificationStatus;
use App\Service\NotificationQueueManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

class NotificationQueueManagerTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private NotificationQueueManager $manager;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->manager = $container->get(NotificationQueueManager::class);

        $metadatas = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->dropSchema($metadatas);
        $schemaTool->createSchema($metadatas);
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
}
