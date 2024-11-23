<?php

declare(strict_types=1);

namespace CreditTransactionService\Tests\TestCase;

use PHPUnit\Framework\TestCase as BaseTestCase;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Mockery;

abstract class TestCase extends BaseTestCase
{
    protected static ?ContainerInterface $container = null;

    protected function getContainer(): ContainerInterface
    {
        if (null === self::$container) {
            $builder = new ContainerBuilder();
            $builder->addDefinitions(__DIR__.'/../../../config/services.php');
            $builder->addDefinitions(__DIR__.'/../../../config/services.testing.php');
            self::$container = $builder->build();
        }

        return self::$container;
    }

    protected function getService(string $id)
    {
        return $this->getContainer()->get($id);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}
