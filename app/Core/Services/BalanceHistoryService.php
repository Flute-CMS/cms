<?php

namespace Flute\Core\Services;

use Flute\Core\Database\Entities\BalanceHistory;
use Flute\Core\Database\Entities\User;

class BalanceHistoryService
{
    /**
     * Record a balance history entry.
     */
    public function record(
        User $user,
        string $type,
        float $amount,
        float $balanceAfter,
        ?string $source = null,
        ?string $description = null,
        ?BalanceHistoryMeta $meta = null,
    ): BalanceHistory {
        $entry = new BalanceHistory();
        $entry->user = $user;
        $entry->type = $type;
        $entry->amount = $amount;
        $entry->balanceAfter = $balanceAfter;
        $entry->source = $source;
        $entry->description = $description;

        if ($meta !== null && !$meta->isEmpty()) {
            $entry->setAdditional($meta->toArray());
        }

        transaction($entry)->run();

        return $entry;
    }

    /**
     * Record a balance deduction (amount is stored as negative).
     */
    public function purchase(
        User $user,
        float $amount,
        float $balanceAfter,
        string $source,
        ?string $description = null,
        ?BalanceHistoryMeta $meta = null,
    ): BalanceHistory {
        return $this->record(
            $user,
            BalanceHistory::TYPE_PURCHASE,
            -abs($amount),
            $balanceAfter,
            $source,
            $description,
            $meta,
        );
    }

    /**
     * Record a balance top-up (amount is stored as positive).
     */
    public function topup(
        User $user,
        float $amount,
        float $balanceAfter,
        ?string $source = null,
        ?string $description = null,
        ?BalanceHistoryMeta $meta = null,
    ): BalanceHistory {
        return $this->record(
            $user,
            BalanceHistory::TYPE_TOPUP,
            abs($amount),
            $balanceAfter,
            $source,
            $description,
            $meta,
        );
    }

    /**
     * Record a balance refund (amount is stored as positive).
     */
    public function refund(
        User $user,
        float $amount,
        float $balanceAfter,
        ?string $source = null,
        ?string $description = null,
        ?BalanceHistoryMeta $meta = null,
    ): BalanceHistory {
        return $this->record(
            $user,
            BalanceHistory::TYPE_REFUND,
            abs($amount),
            $balanceAfter,
            $source,
            $description,
            $meta,
        );
    }
}
