<?php

declare(strict_types=1);

namespace CreditTransactionService\Application\Command;

use CreditTransactionService\Domain\Service\TransactionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Exception;
use InvalidArgumentException;

class ProcessTransactionCommand extends Command
{
    private TransactionService $transactionService;
    private LoggerInterface $logger;

    public function __construct(
        TransactionService $transactionService,
        LoggerInterface $logger
    ) {
        parent::__construct('transaction:process');
        $this->transactionService = $transactionService;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Process a new transaction for a user')
            ->addArgument('userId', InputArgument::REQUIRED, 'The user ID')
            ->addArgument('amount', InputArgument::REQUIRED, 'The transaction amount (can be positive or negative)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $userId = (int)$input->getArgument('userId');
            $amount = (float)$input->getArgument('amount');

            // Validate user ID
            if ($userId <= 0) {
                throw new InvalidArgumentException('User ID must be a positive number.');
            }

            $transaction = $this->transactionService->processTransaction($userId, $amount);

            $amountFormatted = number_format($transaction->getAmount(), 2);
            $type = $amount >= 0 ? 'credit' : 'debit';

            $io->writeln(sprintf(
                '<info>Transaction processed successfully. ID: %d, Amount: %.2f , Type: %s , User ID: %d</info>',
                $transaction->getId(),
                $amountFormatted,
                $type,
                $transaction->getUserId()
            ));

            return Command::SUCCESS;

        } catch (Exception $e) {
            $io->error($e->getMessage());

            $this->logger->error('Command execution failed', [
                'command' => $this->getName(),
                'userId'  => $userId ?? null,
                'amount'  => $amount ?? null,
                'error'   => $e->getMessage()
            ]);

            return Command::FAILURE;
        }
    }
}
