<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Flute\Core\Services\UserService;
use Flute\Core\Modules\Auth\Services\AuthService;
use Flute\Core\Database\Entities\User;
use Flute\Core\Exceptions\BalanceNotEnoughException;

class UserServiceTest extends TestCase
{
    private UserService $userService;

    protected function setUp() : void
    {
        parent::setUp();

        $authMock = $this->createMock(AuthService::class);
        $this->userService = new UserService($authMock);
    }

    public function testTopupIncreasesBalance() : void
    {
        $user = new User();
        $user->balance = 100;

        $this->userService->topup(50, $user);

        $this->assertEquals(150, $user->balance);
    }

    public function testUnbalanceDecreasesBalance() : void
    {
        $user = new User();
        $user->balance = 100;

        $this->userService->unbalance(30, $user);

        $this->assertEquals(70, $user->balance);
    }

    public function testUnbalanceThrowsIfNotEnoughMoney() : void
    {
        $this->expectException(BalanceNotEnoughException::class);

        $user = new User();
        $user->balance = 10;
        $this->userService->unbalance(20, $user);
    }
}
