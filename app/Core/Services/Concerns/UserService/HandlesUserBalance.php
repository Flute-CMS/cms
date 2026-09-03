<?php

namespace Flute\Core\Services\Concerns\UserService;

use Flute\Core\Database\Entities\User;
use Flute\Core\Events\UserChangedEvent;
use Flute\Core\Exceptions\BalanceNotEnoughException;
use Flute\Core\Exceptions\UserNotFoundException;
use Flute\Core\Services\BalanceHistoryMeta;
use Flute\Core\Services\BalanceHistoryService;
use InvalidArgumentException;
use Throwable;

trait HandlesUserBalance
{
    /**
     * @throws Throwable
     */
    public function topup(
        float $sum,
        ?User $user = null,
        ?string $source = null,
        ?string $description = null,
        ?BalanceHistoryMeta $meta = null,
    ): void {
        if ($sum <= 0) {
            throw new InvalidArgumentException('The sum must be a positive number.');
        }

        $balanceUser = $user ?? $this->getCurrentUser();

        if (!$balanceUser) {
            throw new UserNotFoundException();
        }

        $database = db();
        $database->begin();
        try {
            $balanceUser = User::query()
                ->forUpdate()
                ->where(['id' => $balanceUser->id])
                ->fetchOne();

            $balanceUser->balance += $sum;
            transaction($balanceUser)->run();
            $database->commit();
        } catch (\Throwable $e) {
            $database->rollback();
            throw $e;
        }

        try {
            app(BalanceHistoryService::class)->topup(
                $balanceUser,
                $sum,
                $balanceUser->balance,
                $source ?? 'payment',
                $description,
                $meta,
            );
        } catch (\Throwable $e) {
            logs()->error('Balance history record failed: ' . $e->getMessage());
        }

        if (function_exists('notify')) {
            try {
                notify('core.balance_topup', $balanceUser, [
                    'amount' => number_format($sum, 2),
                    'balance' => number_format($balanceUser->balance, 2),
                ]);
            } catch (Throwable $e) {
                logs()->error('Notification [core.balance_topup] failed: ' . $e->getMessage());
            }
        }

        events()->dispatch(new UserChangedEvent($balanceUser), UserChangedEvent::NAME);
    }

    public function refund(
        float $sum,
        ?User $user = null,
        ?string $source = null,
        ?string $description = null,
        ?BalanceHistoryMeta $meta = null,
    ): void {
        if ($sum <= 0) {
            throw new InvalidArgumentException('The sum must be a positive number.');
        }

        $balanceUser = $user ?? $this->getCurrentUser();

        if (!$balanceUser) {
            throw new UserNotFoundException();
        }

        $database = db();
        $database->begin();
        try {
            $balanceUser = User::query()
                ->forUpdate()
                ->where(['id' => $balanceUser->id])
                ->fetchOne();

            $balanceUser->balance += $sum;
            transaction($balanceUser)->run();
            $database->commit();
        } catch (\Throwable $e) {
            $database->rollback();
            throw $e;
        }

        try {
            app(BalanceHistoryService::class)->refund(
                $balanceUser,
                $sum,
                $balanceUser->balance,
                $source ?? 'refund',
                $description,
                $meta,
            );
        } catch (\Throwable $e) {
            logs()->error('Balance history record failed: ' . $e->getMessage());
        }

        events()->dispatch(new UserChangedEvent($balanceUser), UserChangedEvent::NAME);
    }

    /**
     * @throws BalanceNotEnoughException
     * @throws Throwable
     */
    public function unbalance(
        float $sum,
        ?User $user = null,
        ?string $source = null,
        ?string $description = null,
        ?BalanceHistoryMeta $meta = null,
    ): void {
        $sum = $this->normalizeMoneyAmount($sum);

        if ($sum <= 0) {
            throw new InvalidArgumentException('The sum must be a positive number.');
        }

        $balanceUser = $user ?? $this->getCurrentUser();

        if (!$balanceUser) {
            throw new UserNotFoundException();
        }

        $database = db();
        $database->begin();
        try {
            $balanceUser = User::query()
                ->forUpdate()
                ->where(['id' => $balanceUser->id])
                ->fetchOne();

            $balanceMinor = $this->moneyToMinorUnits((float) $balanceUser->balance);
            $sumMinor = $this->moneyToMinorUnits($sum);

            if ($balanceMinor < $sumMinor) {
                $neededSum = $this->minorUnitsToMoney($sumMinor - $balanceMinor);
                $database->rollback();

                throw ( new BalanceNotEnoughException() )->setNeededSum($neededSum);
            }

            $balanceUser->balance -= $sum;
            transaction($balanceUser)->run();
            $database->commit();
        } catch (BalanceNotEnoughException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $database->rollback();
            throw $e;
        }

        try {
            app(BalanceHistoryService::class)->purchase(
                $balanceUser,
                $sum,
                $balanceUser->balance,
                $source ?? 'system',
                $description,
                $meta,
            );
        } catch (\Throwable $e) {
            logs()->error('Balance history record failed: ' . $e->getMessage());
        }

        events()->dispatch(new UserChangedEvent($balanceUser), UserChangedEvent::NAME);
    }

    public function hasEnoughBalance(float $sum): bool
    {
        try {
            if (!$this->isLoggedIn()) {
                return false;
            }

            $balanceMinor = $this->moneyToMinorUnits((float) ( $this->currentUser->balance ?? 0 ));
            $sumMinor = $this->moneyToMinorUnits($sum);

            return $balanceMinor >= $sumMinor;
        } catch (Throwable $e) {
            $this->handleAuthStateFailure('Balance check failed', $e);

            return false;
        }
    }

    private function normalizeMoneyAmount(float $amount): float
    {
        return $this->minorUnitsToMoney($this->moneyToMinorUnits($amount));
    }

    private function moneyToMinorUnits(float $amount): int
    {
        return (int) round($amount * 100);
    }

    private function minorUnitsToMoney(int $amount): float
    {
        return round($amount / 100, 2);
    }
}
