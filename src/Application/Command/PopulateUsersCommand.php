<?php

declare(strict_types=1);

namespace CreditTransactionService\Application\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use CreditTransactionService\Domain\Service\UserService;
use Psr\Log\LoggerInterface;
use Exception;

class PopulateUsersCommand extends Command
{
    private UserService $userService;
    private LoggerInterface $logger;

    public function __construct(UserService $userService, LoggerInterface $logger)
    {
        parent::__construct();
        $this->userService = $userService;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this->setName('users:populate')
            ->setDescription('Populate database with random users')
            ->addOption(
                'count',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Number of users to generate',
                10
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $count = (int) $input->getOption('count');
            $users = $this->userService->generateRandomUsers($count);

            $output->writeln(sprintf('Successfully generated %d users', count($users)));
            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->logger->error('Error populating users: '.$e->getMessage());
            $output->writeln('<error>'.$e->getMessage().'</error>');
            return Command::FAILURE;
        }
    }
}
