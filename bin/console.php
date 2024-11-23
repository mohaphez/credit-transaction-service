#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use CreditTransactionService\Application\Bootstrap;
use CreditTransactionService\Application\Console\Application;

try {

    Bootstrap::init();

    $application = new Application(Bootstrap::getContainer());
    $application->run();
} catch (Throwable $e) {
    fwrite(STDERR, sprintf("Error: %s\n", $e->getMessage()));
    exit(1);
}
