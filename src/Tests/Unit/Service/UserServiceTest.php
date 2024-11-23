<?php

declare(strict_types=1);

namespace CreditTransactionService\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use CreditTransactionService\Domain\Service\UserService;
use CreditTransactionService\Domain\Entity\User;
use CreditTransactionService\Domain\Exception\UserNotFoundException;
use CreditTransactionService\Domain\Exception\InsufficientCreditException;

class UserServiceTest extends TestCase
{
    private \CreditTransactionService\Domain\Repository\UserRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject $userRepository;
    private \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger;
    private UserService $userService;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(\CreditTransactionService\Domain\Repository\UserRepositoryInterface::class);
        $this->logger = $this->createMock(\Psr\Log\LoggerInterface::class);

        $this->userService = new UserService(
            $this->userRepository,
            $this->logger
        );
    }

    public function testGenerateRandomUsersSuccessfully(): void
    {
        $count = 2;
        $this->userRepository
            ->expects($this->exactly($count))
            ->method('save')
            ->willReturnCallback(function ($user) {
                static $id = 1;
                return new User($id++, $user->getName(), $user->getCredit());
            });

        $users = $this->userService->generateRandomUsers($count);

        $this->assertCount($count, $users);
        $this->assertInstanceOf(User::class, $users[0]);
        $this->assertNotNull($users[0]->getId());
    }

    public function testGetUserByIdThrowsNotFoundException(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(null);

        $this->expectException(UserNotFoundException::class);

        $this->userService->getUserById(1);
    }

    public function testUpdateUserCreditSuccessfully(): void
    {
        $user = new User(1, 'Test User', 1000.0);
        $amount = -500.0;

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($user);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(fn ($user) => $user);

        $updatedUser = $this->userService->updateUserCredit(1, $amount);

        $this->assertEquals(500.0, $updatedUser->getCredit());
    }

    public function testUpdateUserCreditThrowsInsufficientCreditException(): void
    {
        $user = new User(1, 'Test User', 100.0);
        $amount = -200.0;

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($user);

        $this->expectException(InsufficientCreditException::class);

        $this->userService->updateUserCredit(1, $amount);
    }
}
