<?php

namespace App\Tests\Unit;

use App\Dto\NotificationRequestDto;
use App\Validator\NotificationContentValidator;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

#[AllowMockObjectsWithoutExpectations]
class SimpleConfigurationTest extends TestCase
{
    public function testAllChannelsEnabled(): void
    {
        $enabledChannels = ['email', 'sms', 'push'];
        $validator = new NotificationContentValidator($enabledChannels);
        
        $context = $this->createMock(ExecutionContext::class);
        $context->expects($this->never())->method('buildViolation');
        
        $validator->initialize($context);

        $dto = new NotificationRequestDto(
            userId: 'user123',
            channels: ['email', 'sms'],
            scheduledDate: null,
            content: [
                'subject' => 'Test',
                'body' => 'Test message',
                'email' => 'test@example.com',
                'phone' => '+1234567890'
            ]
        );

        $validator->validate($dto, $this->createMock(\App\Validator\NotificationContent::class));
    }

    public function testDisabledChannel(): void
    {
        $enabledChannels = ['email']; // Only email enabled
        $validator = new NotificationContentValidator($enabledChannels);
        
        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $context = $this->createMock(ExecutionContext::class);
        
        $context->expects($this->once())
            ->method('buildViolation')
            ->with("Channel 'sms' is disabled.")
            ->willReturn($violationBuilder);
            
        $violationBuilder->method('atPath')->willReturnSelf();
        $violationBuilder->method('setParameter')->willReturnSelf();
        $violationBuilder->method('addViolation')->willReturnSelf();
        
        $validator->initialize($context);

        $dto = new NotificationRequestDto(
            userId: 'user123',
            channels: ['email', 'sms'], // sms is disabled
            scheduledDate: null,
            content: [
                'subject' => 'Test',
                'body' => 'Test message',
                'email' => 'test@example.com',
                'phone' => '+1234567890'
            ]
        );

        $validator->validate($dto, $this->createMock(\App\Validator\NotificationContent::class));
    }

    public function testEmptyConfiguration(): void
    {
        $enabledChannels = []; // No channels enabled
        $validator = new NotificationContentValidator($enabledChannels);
        
        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $context = $this->createMock(ExecutionContext::class);
        
        $context->expects($this->exactly(2))
            ->method('buildViolation')
            ->willReturn($violationBuilder);
            
        $violationBuilder->method('atPath')->willReturnSelf();
        $violationBuilder->method('setParameter')->willReturnSelf();
        $violationBuilder->method('addViolation')->willReturnSelf();
        
        $validator->initialize($context);

        $dto = new NotificationRequestDto(
            userId: 'user123',
            channels: ['email', 'sms'],
            scheduledDate: null,
            content: [
                'subject' => 'Test',
                'body' => 'Test message',
                'email' => 'test@example.com',
                'phone' => '+1234567890'
            ]
        );

        $validator->validate($dto, $this->createMock(\App\Validator\NotificationContent::class));
    }
}
