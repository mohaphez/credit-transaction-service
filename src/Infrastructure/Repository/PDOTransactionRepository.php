<?php

declare(strict_types=1);

namespace CreditTransactionService\Infrastructure\Repository;

use CreditTransactionService\Domain\Entity\Transaction;
use CreditTransactionService\Domain\Repository\TransactionRepositoryInterface;
use DateTimeImmutable;
use PDO;
use PDOException;
use Psr\Log\LoggerInterface;
use Redis;
use DateMalformedStringException;
use Exception;
use RuntimeException;

class PDOTransactionRepository implements TransactionRepositoryInterface
{
    private PDO $connection;
    private Redis $cache;
    private LoggerInterface $logger;
    private const int CACHE_TTL = 3600;

    public function __construct(
        PDO $connection,
        Redis $cache,
        LoggerInterface $logger
    ) {
        $this->connection = $connection;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    public function countTransactionsByDate(DateTimeImmutable $date): int
    {
        try {
            $cacheKey = "daily_transactions_count_".$date->format('Y-m-d');

            // Try to get from cache first
            $cachedCount = $this->cache->get($cacheKey);
            if (false !== $cachedCount) {
                $this->logger->debug('Retrieved transaction count from cache', [
                    'date'  => $date->format('Y-m-d'),
                    'count' => $cachedCount
                ]);
                return (int)$cachedCount;
            }

            // If not in cache, query the database
            $stmt = $this->connection->prepare(
                "SELECT COUNT(*) as transaction_count 
                 FROM transactions 
                 WHERE DATE(transaction_date) = :date"
            );

            $stmt->execute([':date' => $date->format('Y-m-d')]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $count = (int)($result['transaction_count'] ?? 0);

            // Cache the result
            $this->cache->setex($cacheKey, self::CACHE_TTL, $count);

            $this->logger->debug('Retrieved transaction count from database', [
                'date'  => $date->format('Y-m-d'),
                'count' => $count
            ]);

            return $count;

        } catch (PDOException $e) {
            $this->logger->error('Failed to count transactions by date', [
                'date'  => $date->format('Y-m-d'),
                'error' => $e->getMessage()
            ]);
            throw new RuntimeException('Failed to count transactions: '.$e->getMessage(), 0, $e);
        }
    }

    public function save(Transaction $transaction): Transaction
    {
        $stmt = $this->connection->prepare("INSERT INTO transactions (amount, user_id, transaction_date) VALUES (:amount, :user_id, :transaction_date)");

        $stmt->execute([
            ':amount'           => $transaction->getAmount(),
            ':user_id'          => $transaction->getUserId(),
            ':transaction_date' => $transaction->getTransactionDate()->format('Y-m-d')
        ]);


        $dateString = $transaction->getTransactionDate()->format('Y-m-d');
        $this->cache->del([
            "daily_transactions_".$dateString,
            "daily_transactions_count_".$dateString
        ]);

        return new Transaction(
            (int)$this->connection->lastInsertId(),
            $transaction->getAmount(),
            $transaction->getUserId(),
            $transaction->getTransactionDate()
        );
    }

    /**
     * @throws DateMalformedStringException
     */
    public function findByUserAndDate(int $userId, DateTimeImmutable $date): array
    {
        $stmt = $this->connection->prepare("SELECT * FROM transactions WHERE user_id = :user_id AND transaction_date = :transaction_date");

        $stmt->execute([
            ':user_id'          => $userId,
            ':transaction_date' => $date->format('Y-m-d')
        ]);

        $transactions = [];
        while ($transactionData = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $transactions[] = new Transaction(
                (int)$transactionData['id'],
                (float)$transactionData['amount'],
                (int)$transactionData['user_id'],
                new DateTimeImmutable($transactionData['transaction_date'])
            );
        }

        return $transactions;
    }

    public function findTotalAmountByDate(DateTimeImmutable $date): float
    {
        $cacheKey = "daily_transactions_".$date->format('Y-m-d');

        // Try to get from cache first
        $cachedTotal = $this->cache->get($cacheKey);
        if (false !== $cachedTotal) {
            return (float)$cachedTotal;
        }

        // If not in cache, calculate from database
        $stmt = $this->connection->prepare(
            "SELECT SUM(amount) as total_amount FROM transactions 
             WHERE transaction_date = :transaction_date"
        );

        $stmt->execute([':transaction_date' => $date->format('Y-m-d')]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $total = (float)($result['total_amount'] ?? 0);

        // Cache the result for 24 hours
        $this->cache->setex($cacheKey, 86400, $total);

        return $total;
    }

    /**
     * Find all transactions for a specific date
     *
     * @param DateTimeImmutable $date
     * @return array<Transaction>
     * @throws RuntimeException
     */
    public function findByDate(DateTimeImmutable $date): array
    {
        try {
            // Try to get from cache first
            $cacheKey = 'transactions_'.$date->format('Y-m-d');
            $cachedData = $this->cache->get($cacheKey);

            if (false !== $cachedData) {
                $this->logger->info('Retrieved transactions from cache for date: '.$date->format('Y-m-d'));
                return unserialize($cachedData);
            }

            // If not in cache, query the database
            $stmt = $this->connection->prepare(
                "SELECT t.*, u.name as user_name 
                 FROM transactions t 
                 JOIN users u ON t.user_id = u.id 
                 WHERE DATE(t.transaction_date) = :date 
                 ORDER BY t.created_at DESC"
            );

            $stmt->execute([
                ':date' => $date->format('Y-m-d')
            ]);

            $transactions = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $transactions[] = new Transaction(
                    (int)$row['id'],
                    (float)$row['amount'],
                    (int)$row['user_id'],
                    new DateTimeImmutable($row['transaction_date'])
                );
            }

            $this->cache->setex(
                $cacheKey,
                self::CACHE_TTL,
                serialize($transactions)
            );

            $this->logger->info(sprintf(
                'Retrieved %d transactions from database for date: %s',
                count($transactions),
                $date->format('Y-m-d')
            ));

            return $transactions;

        } catch (PDOException $e) {
            $this->logger->error('Database error in findByDate: '.$e->getMessage(), [
                'date'      => $date->format('Y-m-d'),
                'exception' => $e
            ]);
            throw new RuntimeException('Failed to retrieve transactions: '.$e->getMessage(), 0, $e);
        } catch (DateMalformedStringException $e) {
        }
    }

    /**
     * Begin a database transaction
     *
     * @throws RuntimeException
     */
    public function beginTransaction(): void
    {
        try {
            if ($this->connection->inTransaction()) {
                $this->logger->warning('Attempted to begin transaction while another transaction is active');
                return;
            }

            $this->connection->beginTransaction();
            $this->logger->debug('Transaction began successfully');

        } catch (PDOException $e) {
            $this->logger->error('Failed to begin transaction: '.$e->getMessage());
            throw new RuntimeException('Failed to begin transaction: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Commit the current transaction
     *
     * @throws RuntimeException
     */
    public function commit(): void
    {
        try {
            if (!$this->connection->inTransaction()) {
                $this->logger->warning('Attempted to commit when no transaction is active');
                return;
            }

            $this->connection->commit();
            $this->logger->debug('Transaction committed successfully');

            // Clear relevant cache entries after successful commit
            $this->invalidateCache();

        } catch (PDOException $e) {
            $this->logger->error('Failed to commit transaction: '.$e->getMessage());
            throw new RuntimeException('Failed to commit transaction: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Rollback the current transaction
     *
     * @throws RuntimeException
     */
    public function rollback(): void
    {
        try {
            if (!$this->connection->inTransaction()) {
                $this->logger->warning('Attempted to rollback when no transaction is active');
                return;
            }

            $this->connection->rollBack();
            $this->logger->debug('Transaction rolled back successfully');

        } catch (PDOException $e) {
            $this->logger->error('Failed to rollback transaction: '.$e->getMessage());
            throw new RuntimeException('Failed to rollback transaction: '.$e->getMessage(), 0, $e);
        }
    }

    private function invalidateCache(): void
    {
        try {
            $keys = array_merge(
                $this->cache->keys('transactions_*'),
                $this->cache->keys('daily_transactions_*'),
                $this->cache->keys('daily_transactions_count_*')
            );

            if (!empty($keys)) {
                $this->cache->del($keys);
                $this->logger->info(sprintf('Invalidated %d cache entries', count($keys)));
            }
        } catch (Exception $e) {
            $this->logger->warning('Failed to invalidate cache: '.$e->getMessage());
        }
    }


    public function __destruct()
    {
        if ($this->connection->inTransaction()) {
            $this->logger->warning('Uncommitted transaction detected in destructor - rolling back');
            $this->rollback();
        }
    }
}
