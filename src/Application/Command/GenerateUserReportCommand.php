<?php

declare(strict_types=1);

namespace CreditTransactionService\Application\Command;

use CreditTransactionService\Domain\Service\TransactionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DateTimeImmutable;
use Exception;

class GenerateUserReportCommand extends Command
{
    private TransactionService $transactionService;
    private LoggerInterface $logger;

    public function __construct(
        TransactionService $transactionService,
        LoggerInterface $logger
    ) {
        parent::__construct('report:user-daily');
        $this->transactionService = $transactionService;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Generate daily report for a specific user')
            ->addArgument('userId', InputArgument::REQUIRED, 'User ID')
            ->addArgument('date', InputArgument::REQUIRED, 'Report date (Y-m-d)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $userId = (int)$input->getArgument('userId');
            $date = new DateTimeImmutable($input->getArgument('date'));

            $report = $this->transactionService->getUserDailyReport($userId, $date);

            $output->writeln([
                sprintf("Report for User ID: %d", $userId),
                sprintf("Date: %s", $report['date']),
                sprintf("Total Amount: %.2f", $report['total_amount']),
                "",
                "Transactions:"
            ]);

            foreach ($report['transactions'] as $transaction) {
                $output->writeln(sprintf(
                    "  - ID: %d, Amount: %.2f",
                    $transaction->getId(),
                    $transaction->getAmount()
                ));
            }

            return Command::SUCCESS;

        } catch (Exception $e) {
            $output->writeln("<error>Error: {$e->getMessage()}</error>");
            $this->logger->error('Failed to generate user report', [
                'userId' => $userId ?? null,
                'date'   => $date ?? null,
                'error'  => $e->getMessage()
            ]);

            return Command::FAILURE;
        }
    }
}
