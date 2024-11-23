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
use DateTimeImmutable;
use Exception;
use InvalidArgumentException;

class GenerateSystemReportCommand extends Command
{
    private TransactionService $transactionService;
    private LoggerInterface $logger;

    public function __construct(
        TransactionService $transactionService,
        LoggerInterface $logger
    ) {
        parent::__construct('report:system-daily');
        $this->transactionService = $transactionService;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Generate system-wide daily transaction report')
            ->addArgument('date', InputArgument::REQUIRED, 'Report date (Y-m-d)')
            ->setHelp(
                <<<EOT
                    The <info>%command.name%</info> command generates a daily system-wide transaction report:
                    
                      <info>php %command.full_name% 2024-01-01</info>
                    
                    The date must be in Y-m-d format (e.g., 2024-01-01)
                    EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $dateString = $input->getArgument('date');

            // Validate date format
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString)) {
                throw new InvalidArgumentException('Date must be in Y-m-d format (e.g., 2024-01-01)');
            }

            $date = new DateTimeImmutable($dateString);
            $report = $this->transactionService->getSystemDailyReport($date);

            $io->title(sprintf('System Daily Report for %s', $report['date']));

            $io->table(
                ['Metric', 'Value'],
                [
                    ['Total Amount', number_format($report['total_amount'], 2)],
                    ['Total Transactions', number_format($report['total_transactions'])]
                ]
            );

            return Command::SUCCESS;

        } catch (InvalidArgumentException $e) {
            $io->error($e->getMessage());
            return Command::INVALID;

        } catch (Exception $e) {
            $io->error($e->getMessage());
            $this->logger->error('Failed to generate system report', [
                'date'  => $dateString ?? null,
                'error' => $e->getMessage()
            ]);

            return Command::FAILURE;
        }
    }
}
