<?php

declare(strict_types=1);

namespace CreditTransactionService\Domain\Service;

use CreditTransactionService\Domain\Entity\Transaction;
use CreditTransactionService\Domain\Repository\TransactionRepositoryInterface;
use CreditTransactionService\Domain\Repository\UserRepositoryInterface;
use Psr\Log\LoggerInterface;
use DateTimeImmutable;
use DomainException;
use Exception;

class TransactionService
{
    private TransactionRepositoryInterface $transactionRepository;
    private UserRepositoryInterface $userRepository;
    private LoggerInterface $logger;

    public function __construct(
        TransactionRepositoryInterface $transactionRepository,
        UserRepositoryInterface $userRepository,
        LoggerInterface $logger
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->userRepository = $userRepository;
        $this->logger = $logger;
    }

    public function processTransaction(int $userId, float $amount): Transaction
    {
        try {
            $this->transactionRepository->beginTransaction();

            $user = $this->userRepository->findById($userId);
            if (!$user) {
                throw new DomainException("User not found: {$userId}");
            }

            // Create transaction with current date
            $transaction = new Transaction(
                null,
                $amount,
                $userId,
                new DateTimeImmutable('now')
            );

            // Update user's credit
            $user->updateCredit($amount);
            $this->userRepository->save($user);

            // Save transaction
            $savedTransaction = $this->transactionRepository->save($transaction);

            $this->transactionRepository->commit();

            $this->logger->info('Transaction processed successfully', [
                'transactionId' => $savedTransaction->getId(),
                'userId'        => $userId,
                'amount'        => $amount
            ]);

            return $savedTransaction;

        } catch (Exception $e) {
            $this->transactionRepository->rollback();
            $this->logger->error('Transaction failed', [
                'userId' => $userId,
                'amount' => $amount,
                'error'  => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getUserDailyReport(int $userId, DateTimeImmutable $date): array
    {
        $transactions = $this->transactionRepository->findByUserAndDate($userId, $date);

        $totalAmount = array_reduce(
            $transactions,
            fn($carry, Transaction $transaction) => $carry + $transaction->getAmount(),
            0.0
        );

        return [
            'user_id'      => $userId,
            'date'         => $date->format('Y-m-d'),
            'total_amount' => $totalAmount,
            'transactions' => $transactions
        ];
    }

    public function getSystemDailyReport(DateTimeImmutable $date): array
    {
        $totalAmount = $this->transactionRepository->findTotalAmountByDate($date);
        $totalTransactions = $this->transactionRepository->countTransactionsByDate($date);

        return [
            'date'               => $date->format('Y-m-d'),
            'total_amount'       => $totalAmount,
            'total_transactions' => $totalTransactions
        ];
    }
}
