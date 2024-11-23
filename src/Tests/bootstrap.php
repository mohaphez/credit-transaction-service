<?php

declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';

use CreditTransactionService\Infrastructure\Config\Environment;
use CreditTransactionService\Tests\TestCase\DatabaseMigration;


if (file_exists(__DIR__.'/../../.env.testing')) {
    (new Symfony\Component\Dotenv\Dotenv())->load(__DIR__.'/../../.env.testing');
} else {
    (new Symfony\Component\Dotenv\Dotenv())->load(__DIR__.'/../../.env');
}

// Set testing environment
$_ENV['APP_ENV'] = 'testing';
$_SERVER['APP_ENV'] = 'testing';

Environment::load();

DatabaseMigration::migrate();
