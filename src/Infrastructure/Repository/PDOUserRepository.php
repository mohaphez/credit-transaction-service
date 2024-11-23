<?php

declare(strict_types=1);

namespace CreditTransactionService\Infrastructure\Repository;

use CreditTransactionService\Domain\Entity\User;
use CreditTransactionService\Domain\Repository\UserRepositoryInterface;
use PDO;
use Psr\Log\LoggerInterface;
use PDOException;
use RuntimeException;

class PDOUserRepository implements UserRepositoryInterface
{
    private PDO $connection;
    private LoggerInterface $logger;

    public function __construct(PDO $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function save(User $user): User
    {
        try {
            if (null === $user->getId()) {
                $stmt = $this->connection->prepare(
                    'INSERT INTO users (name, credit) VALUES (:name, :credit)'
                );

                $stmt->execute([
                    ':name'   => $user->getName(),
                    ':credit' => $user->getCredit()
                ]);

                return new User(
                    (int)$this->connection->lastInsertId(),
                    $user->getName(),
                    $user->getCredit()
                );
            }

            $stmt = $this->connection->prepare(
                'UPDATE users SET name = :name, credit = :credit WHERE id = :id'
            );

            $stmt->execute([
                ':id'     => $user->getId(),
                ':name'   => $user->getName(),
                ':credit' => $user->getCredit()
            ]);

            return $user;

        } catch (PDOException $e) {
            $this->logger->error('Failed to save user: '.$e->getMessage(), [
                'user'      => $user,
                'exception' => $e
            ]);
            throw new RuntimeException('Failed to save user: '.$e->getMessage(), 0, $e);
        }
    }

    public function findById(int $id): ?User
    {
        try {
            $stmt = $this->connection->prepare('SELECT * FROM users WHERE id = :id');
            $stmt->execute([':id' => $id]);

            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$data) {
                return null;
            }

            return new User(
                (int)$data['id'],
                $data['name'],
                (float)$data['credit']
            );

        } catch (PDOException $e) {
            $this->logger->error('Failed to find user: '.$e->getMessage(), [
                'id'        => $id,
                'exception' => $e
            ]);
            throw new RuntimeException('Failed to find user: '.$e->getMessage(), 0, $e);
        }
    }

    public function findAll(): array
    {
        try {
            $stmt = $this->connection->query('SELECT * FROM users');
            $users = [];

            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $users[] = new User(
                    (int)$data['id'],
                    $data['name'],
                    (float)$data['credit']
                );
            }

            return $users;

        } catch (PDOException $e) {
            $this->logger->error('Failed to fetch all users: '.$e->getMessage(), [
                'exception' => $e
            ]);
            throw new RuntimeException('Failed to fetch all users: '.$e->getMessage(), 0, $e);
        }
    }
}
