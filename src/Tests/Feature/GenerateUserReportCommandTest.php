<?php

declare(strict_types=1);

namespace CreditTransactionService\Tests\Feature;

use CreditTransactionService\Application\Command\GenerateUserReportCommand;
use CreditTransactionService\Domain\Entity\Transaction;
use CreditTransactionService\Domain\Service\TransactionService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use DateTimeImmutable;

class GenerateUserReportCommandTest extends TestCase
{
    private TransactionService $transactionService;
    private LoggerInterface $logger;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->transactionService = $this->createMock(TransactionService::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $command = new GenerateUserReportCommand(
            $this->transactionService,
            $this->logger
        );

        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteSuccessfully(): void
    {
        $userId = 1;
        $date = '2024-01-01';
        $transactions = [
            new Transaction(1, 100.00, $userId, new DateTimeImmutable($date)),
            new Transaction(2, 200.00, $userId, new DateTimeImmutable($date))
        ];

        $report = [
            'user_id'      => $userId,
            'date'         => $date,
            'total_amount' => 300.00,
            'transactions' => $transactions
        ];

        $this->transactionService
            ->expects($this->once())
            ->method('getUserDailyReport')
            ->with(
                $this->equalTo($userId),
                $this->callback(fn($d) => $d->format('Y-m-d') === $date)
            )
            ->willReturn($report);

        $exitCode = $this->commandTester->execute([
            'userId' => $userId,
            'date'   => $date
        ]);

        $this->assertEquals(0, $exitCode);

        $output = $this->commandTester->getDisplay();
        $expectedLines = [
            "Report for User ID: 1",
            "Date: 2024-01-01",
            "Total Amount: 300.00",
            "Transactions:",
            "  - ID: 1, Amount: 100.00",
            "  - ID: 2, Amount: 200.00"
        ];

        foreach ($expectedLines as $line) {
            $this->assertStringContainsString($line, $output);
        }
    }

    public function testExecuteWithInvalidDate(): void
    {
        $userId = 1;
        $invalidDate = 'invalid-date';

        $exitCode = $this->commandTester->execute([
            'userId' => $userId,
            'date'   => $invalidDate
        ]);

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('Error', $this->commandTester->getDisplay());
    }
}
