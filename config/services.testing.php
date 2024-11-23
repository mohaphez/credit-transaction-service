<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\TestHandler;

return [
    LoggerInterface::class => function () {
        $logger = new Logger('testing');
        $logger->pushHandler(new TestHandler());
        return $logger;
    },


    'cache.store' => fn () => new Symfony\Component\Cache\Adapter\ArrayAdapter()
];
