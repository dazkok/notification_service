<?php

namespace App\Tests\Validator;

use App\Dto\NotificationRequestDto;
use App\Enum\NotificationChannel;
use App\Validator\NotificationContentValidator;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

#[AllowMockObjectsWithoutExpectations]
class NotificationContentValidatorTest extends TestCase
{
    private NotificationContentValidator $validator;
    private ExecutionContext $context;
    private ConstraintViolationBuilderInterface $violationBuilder;

    protected function setUp(): void
    {
        $this->violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->context = $this->createMock(ExecutionContext::class);
        
        $this->context->method('buildViolation')
            ->willReturn($this->violationBuilder);
            
        $this->violationBuilder->method('atPath')
            ->willReturnSelf();
        $this->violationBuilder->method('setParameter')
            ->willReturnSelf();
        $this->violationBuilder->method('addViolation')
            ->willReturnSelf();
    }

    public function testValidChannelsWithAllEnabled(): void
    {
        $enabledChannels = ['email', 'sms', 'push'];
        $this->validator = new NotificationContentValidator($enabledChannels);
        $this->validator->initialize($this->context);

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

        // Should not build any violations
        $this->context->expects($this->never())->method('buildViolation');
        
        $this->validator->validate($dto, $this->createMock(\App\Validator\NotificationContent::class));
    }

    public function testDisabledChannelShouldFail(): void
    {
        $enabledChannels = ['email']; // Only email enabled
        $this->validator = new NotificationContentValidator($enabledChannels);
        $this->validator->initialize($this->context);

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

        // Should build violation for disabled channel
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with("Channel 'sms' is disabled.")
            ->willReturn($this->violationBuilder);

        $this->validator->validate($dto, $this->createMock(\App\Validator\NotificationContent::class));
    }

    public function testInvalidChannelShouldFail(): void
    {
        $enabledChannels = ['email', 'sms'];
        $this->validator = new NotificationContentValidator($enabledChannels);
        $this->validator->initialize($this->context);

        $dto = new NotificationRequestDto(
            userId: 'user123',
            channels: ['email', 'invalid_channel'], // invalid_channel doesn't exist
            scheduledDate: null,
            content: [
                'subject' => 'Test',
                'body' => 'Test message',
                'email' => 'test@example.com'
            ]
        );

        // Should build violation for invalid channel
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with("Channel 'invalid_channel' is invalid.")
            ->willReturn($this->violationBuilder);

        $this->validator->validate($dto, $this->createMock(\App\Validator\NotificationContent::class));
    }

    public function testMissingRequiredFieldShouldFail(): void
    {
        $enabledChannels = ['email', 'sms'];
        $this->validator = new NotificationContentValidator($enabledChannels);
        $this->validator->initialize($this->context);

        $dto = new NotificationRequestDto(
            userId: 'user123',
            channels: ['sms'], // sms requires phone field
            scheduledDate: null,
            content: [
                'subject' => 'Test',
                'body' => 'Test message',
                'email' => 'test@example.com'
                // Missing 'phone' field
            ]
        );

        // Should build violation for missing required field
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with("Field 'phone' is required for channel sms.")
            ->willReturn($this->violationBuilder);

        $this->validator->validate($dto, $this->createMock(\App\Validator\NotificationContent::class));
    }

    public function testEmptyEnabledChannelsShouldBlockAll(): void
    {
        $enabledChannels = []; // No channels enabled
        $this->validator = new NotificationContentValidator($enabledChannels);
        $this->validator->initialize($this->context);

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

        // Should build violations for both channels
        $this->context->expects($this->exactly(2))
            ->method('buildViolation')
            ->willReturn($this->violationBuilder);

        $this->validator->validate($dto, $this->createMock(\App\Validator\NotificationContent::class));
    }

    public function testPushChannelRequiresNoAdditionalFields(): void
    {
        $enabledChannels = ['push'];
        $this->validator = new NotificationContentValidator($enabledChannels);
        $this->validator->initialize($this->context);

        $dto = new NotificationRequestDto(
            userId: 'user123',
            channels: ['push'],
            scheduledDate: null,
            content: [
                'subject' => 'Test',
                'body' => 'Test message'
                // No additional fields required for push
            ]
        );

        // Should not build any violations
        $this->context->expects($this->never())->method('buildViolation');
        
        $this->validator->validate($dto, $this->createMock(\App\Validator\NotificationContent::class));
    }
}
