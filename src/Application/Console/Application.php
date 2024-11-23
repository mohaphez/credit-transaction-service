<?php

declare(strict_types=1);

namespace CreditTransactionService\Application\Console;

use Symfony\Component\Console\Application as SymfonyApplication;
use Psr\Container\ContainerInterface;
use CreditTransactionService\Application\Command\PopulateUsersCommand;
use CreditTransactionService\Application\Command\ProcessTransactionCommand;
use CreditTransactionService\Application\Command\GenerateUserReportCommand;
use CreditTransactionService\Application\Command\GenerateSystemReportCommand;

class Application extends SymfonyApplication
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct('Credit Transaction Service', '1.0.0');

        $this->container = $container;
        $this->registerCommands();
    }

    private function registerCommands(): void
    {
        $commands = [
            PopulateUsersCommand::class,
            ProcessTransactionCommand::class,
            GenerateUserReportCommand::class,
            GenerateSystemReportCommand::class
        ];

        foreach ($commands as $commandClass) {
            $this->add($this->container->get($commandClass));
        }
    }
}
