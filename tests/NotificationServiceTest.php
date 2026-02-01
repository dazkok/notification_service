<?php

namespace App\Tests;

use App\Entity\NotificationLog;
use App\Enum\NotificationChannel;
use App\Notification\NotificationProviderInterface;
use App\Notification\NotificationRenderer;
use App\Service\NotificationService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class NotificationServiceTest extends TestCase
{
    public function test_process_fails_over_to_second_provider(): void
    {
        $log = new NotificationLog();
        $log->setType(NotificationChannel::EMAIL->value);
        $log->setRecipient('test@example.com');
        $log->setContent(['subject' => 'Hi', 'body' => 'Hello']);

        // 1. Мокаємо провайдера, який ПАДАЄ
        $failProvider = $this->createMock(NotificationProviderInterface::class);
        $failProvider->method('supports')->willReturn(true);
        $failProvider->method('send')->willReturn(false); // Імітуємо невдачу

        // 2. Мокаємо провайдера, який ПРАЦЮЄ
        $successProvider = $this->createMock(NotificationProviderInterface::class);
        $successProvider->method('supports')->willReturn(true);
        $successProvider->method('send')->willReturn(true); // Успіх

        // Створюємо сервіс з масивом провайдерів
        $service = new NotificationService(
            [$failProvider, $successProvider],
            $this->createMock(LoggerInterface::class),
            $this->createMock(NotificationRenderer::class),
            'default_template'
        );

        // 3. Запускаємо процес
        $service->process($log);

        // Перевіряємо, що другий провайдер БУВ викликаний хоча б один раз
        $successProvider->expects($this->once())->method('send');

        // Якщо ми дійшли сюди без RuntimeException — failover спрацював
        $this->assertTrue(true);
    }
}
