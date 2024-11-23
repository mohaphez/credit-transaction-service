<?php

declare(strict_types=1);

namespace CreditTransactionService\Tests\Unit\Service;

use CreditTransactionService\Domain\Entity\Transaction;
use CreditTransactionService\Domain\Entity\User;
use CreditTransactionService\Domain\Repository\TransactionRepositoryInterface;
use CreditTransactionService\Domain\Repository\UserRepositoryInterface;
use CreditTransactionService\Domain\Service\TransactionService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

class TransactionServiceTest extends TestCase
{
    private TransactionRepositoryInterface $transactionRepository;
    private UserRepositoryInterface $userRepository;
    private LoggerInterface $logger;
    private TransactionService $service;

    protected function setUp(): void
    {
        $this->transactionRepository = $this->createMock(TransactionRepositoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new TransactionService(
            $this->transactionRepository,
            $this->userRepository,
            $this->logger
        );
    }

    public function testProcessTransactionSuccessfully(): void
    {
        $userId = 1;
        $initialCredit = 500.00;
        $transactionAmount = 100.00;
        $expectedFinalCredit = $initialCredit + $transactionAmount;

        $user = new User($userId, 'Test User', $initialCredit);
        $expectedTransaction = new Transaction(1, $transactionAmount, $userId, new DateTimeImmutable());

        // Setup user repository expectations
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn($user);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (User $savedUser) use ($userId, $expectedFinalCredit) {
                return $savedUser->getId() === $userId
                    && abs($savedUser->getCredit() - $expectedFinalCredit) < 0.01;
            }))
            ->willReturn($user);

        // Setup transaction repository expectations
        $this->transactionRepository
            ->expects($this->once())
            ->method('beginTransaction');

        $this->transactionRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Transaction $transaction) use ($userId, $transactionAmount) {
                return $transaction->getUserId() === $userId
                    && abs($transaction->getAmount() - $transactionAmount) < 0.01;
            }))
            ->willReturn($expectedTransaction);

        $this->transactionRepository
            ->expects($this->once())
            ->method('commit');

        $result = $this->service->processTransaction($userId, $transactionAmount);

        $this->assertInstanceOf(Transaction::class, $result);
        $this->assertEquals($transactionAmount, $result->getAmount());
        $this->assertEquals($userId, $result->getUserId());
    }

    public function testProcessTransactionRollbackOnError(): void
    {
        $userId = 1;
        $amount = 100.00;
        $user = new User($userId, 'Test User', 500.00);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn($user);

        $this->transactionRepository
            ->expects($this->once())
            ->method('beginTransaction');

        $this->transactionRepository
            ->expects($this->once())
            ->method('save')
            ->willThrowException(new RuntimeException('Database error'));

        $this->transactionRepository
            ->expects($this->once())
            ->method('rollback');

        $this->expectException(RuntimeException::class);
        $this->service->processTransaction($userId, $amount);
    }

    public function testGenerateUserDailyReport(): void
    {
        $userId = 1;
        $date = new DateTimeImmutable();
        $transactions = [
            new Transaction(1, 100.00, $userId, $date),
            new Transaction(2, 200.00, $userId, $date)
        ];

        $this->transactionRepository
            ->expects($this->once())
            ->method('findByUserAndDate')
            ->with($userId, $this->callback(fn($d) => $d->format('Y-m-d') === $date->format('Y-m-d')))
            ->willReturn($transactions);

        $report = $this->service->getUserDailyReport($userId, $date);

        $this->assertEquals(2, count($report['transactions']));
        $this->assertEquals(300.00, $report['total_amount']);
    }

}
