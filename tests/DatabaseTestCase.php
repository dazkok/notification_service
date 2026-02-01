<?php

namespace App\Tests;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class DatabaseTestCase extends KernelTestCase
{
    protected function setUp(): void
    {
        StaticDriver::setKeepStaticConnections(true);
        
        parent::setUp();
        
        self::bootKernel();
        
        $this->createSchema();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        StaticDriver::setKeepStaticConnections(false);
    }

    private function createSchema(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        
        $metadatas = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);
        
        // Drop and recreate schema to ensure clean state
        $schemaTool->dropSchema($metadatas);
        $schemaTool->createSchema($metadatas);
    }
}
