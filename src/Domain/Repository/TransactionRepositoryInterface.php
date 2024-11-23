<?php

declare(strict_types=1);

namespace CreditTransactionService\Domain\Repository;

use CreditTransactionService\Domain\Entity\Transaction;
use DateTimeImmutable;

interface TransactionRepositoryInterface
{
    public function save(Transaction $transaction): Transaction;
    public function findByUserAndDate(int $userId, DateTimeImmutable $date): array;
    public function findByDate(DateTimeImmutable $date): array;
    public function findTotalAmountByDate(DateTimeImmutable $date): float;

    public function beginTransaction(): void;
    public function commit(): void;
    public function rollback(): void;
}
