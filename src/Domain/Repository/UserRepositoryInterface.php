<?php

declare(strict_types=1);

namespace CreditTransactionService\Domain\Repository;

use CreditTransactionService\Domain\Entity\User;

interface UserRepositoryInterface
{
    public function save(User $user): User;
    public function findById(int $id): ?User;
    public function findAll(): array;
}
