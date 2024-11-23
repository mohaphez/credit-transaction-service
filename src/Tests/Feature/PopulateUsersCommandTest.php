<?php

declare(strict_types=1);

namespace CreditTransactionService\Tests\Feature;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use CreditTransactionService\Application\Command\PopulateUsersCommand;
use CreditTransactionService\Domain\Entity\User;
use RuntimeException;

class PopulateUsersCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private $userService;
    private $logger;

    protected function setUp(): void
    {
        $this->userService = $this->createMock(\CreditTransactionService\Domain\Service\UserService::class);
        $this->logger = $this->createMock(\Psr\Log\LoggerInterface::class);

        $command = new PopulateUsersCommand($this->userService, $this->logger);
        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteSuccessfully(): void
    {
        $expectedUsers = [
            new User(1, 'User 1', 1000.0),
            new User(2, 'User 2', 2000.0)
        ];

        $this->userService
            ->expects($this->once())
            ->method('generateRandomUsers')
            ->with(2)
            ->willReturn($expectedUsers);

        $exitCode = $this->commandTester->execute([
            '--count' => 2
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Successfully generated 2 users', $this->commandTester->getDisplay());
    }

    public function testExecuteWithError(): void
    {
        $this->userService
            ->expects($this->once())
            ->method('generateRandomUsers')
            ->willThrowException(new RuntimeException('Database error'));

        $exitCode = $this->commandTester->execute([
            '--count' => 2
        ]);

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('Database error', $this->commandTester->getDisplay());
    }
}
