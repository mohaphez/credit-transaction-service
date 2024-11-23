<?php

declare(strict_types=1);

namespace CreditTransactionService\Domain\Service;

use CreditTransactionService\Domain\Entity\User;
use CreditTransactionService\Domain\Repository\UserRepositoryInterface;
use CreditTransactionService\Domain\Exception\InsufficientCreditException;
use CreditTransactionService\Domain\Exception\UserNotFoundException;
use Faker\Factory as FakerFactory;
use Psr\Log\LoggerInterface;
use Exception;

class UserService
{
    private UserRepositoryInterface $userRepository;
    private LoggerInterface $logger;

    public function __construct(
        UserRepositoryInterface $userRepository,
        LoggerInterface $logger
    ) {
        $this->userRepository = $userRepository;
        $this->logger = $logger;
    }

    /**
     * Generate random users with Faker
     */
    public function generateRandomUsers(int $count): array
    {
        $faker = FakerFactory::create();
        $users = [];

        try {
            for ($i = 0; $i < $count; $i++) {
                $user = new User(
                    null,
                    $faker->name,
                    $faker->randomFloat(2, 1000, 10000)
                );
                $users[] = $this->userRepository->save($user);
            }

            $this->logger->info(sprintf('Generated %d random users', $count));
            return $users;
        } catch (Exception $e) {
            $this->logger->error('Error generating random users: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get user by ID with validation
     */
    public function getUserById(int $id): User
    {
        $user = $this->userRepository->findById($id);

        if (!$user) {
            $this->logger->warning(sprintf('User not found with ID: %d', $id));
            throw new UserNotFoundException(sprintf('User not found with ID: %d', $id));
        }

        return $user;
    }

    /**
     * Update user's credit
     */
    public function updateUserCredit(int $userId, float $amount): User
    {
        $user = $this->getUserById($userId);

        if ($amount < 0 && abs($amount) > $user->getCredit()) {
            throw new InsufficientCreditException('Insufficient credit for transaction');
        }

        $user->updateCredit($amount);
        return $this->userRepository->save($user);
    }

    /**
     * Get all users with pagination
     */
    public function getAllUsers(int $page = 1, int $limit = 10): array
    {
        return $this->userRepository->findAll($page, $limit);
    }
}
