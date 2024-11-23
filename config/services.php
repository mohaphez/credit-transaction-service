<?php

// config/services.php

declare(strict_types=1);

use CreditTransactionService\Domain\Repository\UserRepositoryInterface;
use CreditTransactionService\Domain\Repository\TransactionRepositoryInterface;
use CreditTransactionService\Infrastructure\Repository\PDOUserRepository;
use CreditTransactionService\Infrastructure\Repository\PDOTransactionRepository;
use CreditTransactionService\Domain\Service\UserService;
use CreditTransactionService\Domain\Service\TransactionService;
use CreditTransactionService\Application\Command\PopulateUsersCommand;
use CreditTransactionService\Application\Command\ProcessTransactionCommand;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface;

use function DI\autowire;
use function DI\create;
use function DI\get;

return [
    // Database Configuration
    'db.host' => $_ENV['DB_HOST'] ?? 'localhost',
    'db.port' => $_ENV['DB_PORT'] ?? '3306',
    'db.name' => $_ENV['DB_DATABASE'] ?? 'credit_transactions',
    'db.user' => $_ENV['DB_USERNAME'] ?? 'root',
    'db.pass' => $_ENV['DB_PASSWORD'] ?? '',

    // Redis Configuration
    'redis.host' => $_ENV['REDIS_HOST'] ?? 'localhost',
    'redis.port' => $_ENV['REDIS_PORT'] ?? 6379,

    // PDO Connection
    PDO::class => function ($container) {
        $host = $container->get('db.host');
        $port = $container->get('db.port');
        $dbname = $container->get('db.name');
        $username = $container->get('db.user');
        $password = $container->get('db.pass');

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

        return new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    },

    // Redis Connection
    Redis::class => function ($container) {
        $redis = new Redis();
        $redis->connect(
            $container->get('redis.host'),
            (int)$container->get('redis.port')
        );
        return $redis;
    },

    // Logger Configuration
    LoggerInterface::class => function () {
        $logger = new Logger('credit-transaction-service');
        $logger->pushHandler(new StreamHandler(
            __DIR__.'/../var/log/app.log',
            Logger::DEBUG
        ));
        return $logger;
    },

    // Repositories
    UserRepositoryInterface::class        => autowire(PDOUserRepository::class),
    TransactionRepositoryInterface::class => autowire(PDOTransactionRepository::class),

    // Services
    UserService::class => create()
        ->constructor(
            get(UserRepositoryInterface::class),
            get(LoggerInterface::class)
        ),

    TransactionService::class => create()
        ->constructor(
            get(TransactionRepositoryInterface::class),
            get(UserRepositoryInterface::class),
            get(LoggerInterface::class)
        ),

    // Commands
    PopulateUsersCommand::class => create()
        ->constructor(
            get(UserService::class),
            get(LoggerInterface::class)
        ),

    ProcessTransactionCommand::class => create()
        ->constructor(
            get(TransactionService::class),
            get(LoggerInterface::class)
        ),

];
