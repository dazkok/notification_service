<?php

namespace App\Tests;

use App\Entity\NotificationLog;
use App\Enum\NotificationChannel;
use App\Notification\NotificationProviderInterface;
use App\Notification\NotificationRenderer;
use App\Service\NotificationService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class NotificationServiceTest extends TestCase
{
    public function test_process_fails_over_to_second_provider(): void
    {
        $log = new NotificationLog();
        $log->setType(NotificationChannel::EMAIL->value);
        $log->setRecipient('test@example.com');
        $log->setContent(['subject' => 'Hi', 'body' => 'Hello']);

        $failProvider = $this->createMock(NotificationProviderInterface::class);
        $failProvider->method('supports')->willReturn(true);
        $failProvider->method('send')->willReturn(false); // We simulate failure

        $successProvider = $this->createMock(NotificationProviderInterface::class);
        $successProvider->method('supports')->willReturn(true);
        $successProvider->expects($this->once())->method('send')->willReturn(true);

        $service = new NotificationService(
            [$failProvider, $successProvider],
            $this->createMock(LoggerInterface::class),
            $this->createMock(NotificationRenderer::class),
            'default_template'
        );

        $service->process($log);

        $this->assertTrue(true);
    }
}
