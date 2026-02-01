<?php

namespace App\Tests\Integration;

use App\Dto\NotificationRequestDto;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ConfigurationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();
        
        self::bootKernel();
        $this->validator = self::getContainer()->get('validator');
    }

    public function testConfigurationWithDefaultEnabledChannels(): void
    {
        // Test with default configuration (email, sms enabled)
        $dto = new NotificationRequestDto(
            userId: 'user123',
            channels: ['email', 'sms'],
            scheduledDate: null,
            content: [
                'subject' => 'Test Subject',
                'body' => 'Test Body',
                'email' => 'test@example.com',
                'phone' => '+1234567890'
            ]
        );

        $violations = $this->validator->validate($dto);
        
        // Should have no violations
        $this->assertCount(0, $violations);
    }

    public function testConfigurationBlocksDisabledChannels(): void
    {
        // Test that push is disabled by default
        $dto = new NotificationRequestDto(
            userId: 'user123',
            channels: ['push'],
            scheduledDate: null,
            content: [
                'subject' => 'Test Subject',
                'body' => 'Test Body'
            ]
        );

        $violations = $this->validator->validate($dto);
        
        // Should have violation for disabled channel
        $this->assertGreaterThan(0, $violations);
        
        $found = false;
        foreach ($violations as $violation) {
            if (str_contains($violation->getMessage(), 'disabled')) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Should have violation for disabled channel');
    }

    public function testAllChannelsDisabled(): void
    {
        // Test scenario where no channels are enabled
        $dto = new NotificationRequestDto(
            userId: 'user123',
            channels: ['email', 'sms', 'push'],
            scheduledDate: null,
            content: [
                'subject' => 'Test Subject',
                'body' => 'Test Body',
                'email' => 'test@example.com',
                'phone' => '+1234567890'
            ]
        );

        $violations = $this->validator->validate($dto);
        
        // Should have violations for disabled channels
        $this->assertGreaterThan(0, $violations);
    }

    public function testRequiredFieldsValidation(): void
    {
        // Test missing required fields
        $dto = new NotificationRequestDto(
            userId: 'user123',
            channels: ['email', 'sms'],
            scheduledDate: null,
            content: [
                'subject' => 'Test Subject',
                'body' => 'Test Body'
                // Missing email and phone
            ]
        );

        $violations = $this->validator->validate($dto);
        
        // Should have violations for missing required fields
        $this->assertGreaterThan(0, $violations);
        
        $messages = [];
        foreach ($violations as $violation) {
            $messages[] = $violation->getMessage();
        }
        
        $this->assertTrue(
            in_array("Field 'email' is required for channel email.", $messages) ||
            in_array("Field 'phone' is required for channel sms.", $messages),
            'Should have violations for missing required fields'
        );
    }
}
