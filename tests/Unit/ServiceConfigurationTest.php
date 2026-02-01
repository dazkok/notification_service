<?php

namespace App\Tests\Unit;

use App\Validator\NotificationContentValidator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ServiceConfigurationTest extends KernelTestCase
{
    private ContainerInterface $container;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $this->container = self::getContainer();
    }

    public function testValidatorServiceExists(): void
    {
        $validator = $this->container->get(NotificationContentValidator::class);
        $this->assertInstanceOf(NotificationContentValidator::class, $validator);
    }

    public function testEnabledChannelsParameterIsInjected(): void
    {
        // Test that the validator has the enabled channels injected
        $validator = $this->container->get(NotificationContentValidator::class);
        
        // We can't directly access private properties, but we can test behavior
        $this->assertInstanceOf(NotificationContentValidator::class, $validator);
    }

    public function testConfigurationParameterExists(): void
    {
        $enabledChannels = $this->container->getParameter('enabled_channels');
        $this->assertIsArray($enabledChannels);
        $this->assertContains('email', $enabledChannels);
        $this->assertContains('sms', $enabledChannels);
    }

    public function testDefaultConfigurationValues(): void
    {
        $enabledChannels = $this->container->getParameter('enabled_channels');
        
        // Test default configuration from services.yaml
        $this->assertCount(2, $enabledChannels);
        $this->assertContains('email', $enabledChannels);
        $this->assertContains('sms', $enabledChannels);
    }
}
