<?php

declare(strict_types=1);

namespace CreditTransactionService\Domain\Entity;

use DateTimeImmutable;

class Transaction
{
    private ?int $id;
    private float $amount;
    private DateTimeImmutable $transactionDate;
    private int $userId;

    public function __construct(
        ?int $id,
        float $amount,
        int $userId,
        ?DateTimeImmutable $transactionDate = null
    ) {
        $this->id = $id;
        $this->amount = $amount;
        $this->userId = $userId;
        $this->transactionDate = $transactionDate ?? new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getTransactionDate(): DateTimeImmutable
    {
        return $this->transactionDate;
    }
}
