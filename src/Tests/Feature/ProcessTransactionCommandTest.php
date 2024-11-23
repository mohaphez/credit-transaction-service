<?php

declare(strict_types=1);

namespace CreditTransactionService\Tests\Feature;

use CreditTransactionService\Application\Command\ProcessTransactionCommand;
use CreditTransactionService\Domain\Entity\Transaction;
use CreditTransactionService\Domain\Service\TransactionService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use RuntimeException;

class ProcessTransactionCommandTest extends TestCase
{
    private TransactionService $transactionService;
    private LoggerInterface $logger;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->transactionService = $this->createMock(TransactionService::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $command = new ProcessTransactionCommand(
            $this->transactionService,
            $this->logger
        );

        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteWithError(): void
    {
        $userId = 1;
        $amount = 100.50;
        $errorMessage = 'Transaction failed';

        $this->transactionService
            ->expects($this->once())
            ->method('processTransaction')
            ->willThrowException(new RuntimeException($errorMessage));

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Command execution failed',
                $this->callback(fn ($context) => $context['error'] === $errorMessage)
            );

        $exitCode = $this->commandTester->execute([
            'userId' => $userId,
            'amount' => $amount
        ]);

        $this->assertEquals(1, $exitCode);
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('[ERROR]', $display);
        $this->assertStringContainsString($errorMessage, $display);
    }

    public function testExecuteWithInvalidUserId(): void
    {
        $userId = -1;
        $amount = 100.50;

        $exitCode = $this->commandTester->execute([
            'userId' => $userId,
            'amount' => $amount
        ]);

        $this->assertEquals(1, $exitCode);
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('[ERROR]', $display);
        $this->assertStringContainsString('User ID must be a positive number', $display);
    }

    public function testExecuteWithNegativeAmount(): void
    {
        $userId = 1;
        $amount = -100.50;

        // Create a proper mock Transaction with the expected values
        $mockTransaction = $this->createMock(Transaction::class);
        $mockTransaction->method('getId')->willReturn(123);
        $mockTransaction->method('getAmount')->willReturn($amount);
        $mockTransaction->method('getUserId')->willReturn($userId);

        // Configure the service mock to return our transaction mock
        $this->transactionService
            ->expects($this->once())
            ->method('processTransaction')
            ->with($userId, $amount)
            ->willReturn($mockTransaction);

        $exitCode = $this->commandTester->execute([
            'userId' => $userId,
            'amount' => $amount
        ]);

        $this->assertEquals(0, $exitCode);
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Transaction processed successfully', $display);
        $this->assertStringContainsString('ID: 123', $display);
        $this->assertStringContainsString('Amount: -100.50', $display);
        $this->assertStringContainsString('Type: debit', $display);
        $this->assertStringContainsString('User ID: 1', $display);
    }

    public function testExecuteWithPositiveAmount(): void
    {
        $userId = 1;
        $amount = 100.50;

        // Create a proper mock Transaction with the expected values
        $mockTransaction = $this->createMock(Transaction::class);
        $mockTransaction->method('getId')->willReturn(124);
        $mockTransaction->method('getAmount')->willReturn($amount);
        $mockTransaction->method('getUserId')->willReturn($userId);

        // Configure the service mock to return our transaction mock
        $this->transactionService
            ->expects($this->once())
            ->method('processTransaction')
            ->with($userId, $amount)
            ->willReturn($mockTransaction);

        $exitCode = $this->commandTester->execute([
            'userId' => $userId,
            'amount' => $amount
        ]);

        $this->assertEquals(0, $exitCode);
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Transaction processed successfully', $display);
        $this->assertStringContainsString('ID: 124', $display);
        $this->assertStringContainsString('Amount: 100.50', $display);
        $this->assertStringContainsString('Type: credit', $display);
        $this->assertStringContainsString('User ID: 1', $display);
    }
}
