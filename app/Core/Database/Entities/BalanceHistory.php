<?php

namespace Flute\Core\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Table\Index;
use DateTimeImmutable;
use Flute\Core\Services\BalanceHistoryMeta;

#[Entity]
#[Index(columns: ["user_id", "created_at"])]
#[Index(columns: ["type"])]
class BalanceHistory extends ActiveRecord
{
    public const TYPE_TOPUP = 'topup';
    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_REFUND = 'refund';
    public const TYPE_ADMIN = 'admin';

    #[Column(type: "primary")]
    public int $id;

    #[BelongsTo(target: "User", nullable: false)]
    public User $user;

    /** One of TYPE_* constants. */
    #[Column(type: "string", default: "purchase")]
    public string $type = self::TYPE_PURCHASE;

    /** Positive for credit, negative for debit. */
    #[Column(type: "float")]
    public float $amount;

    /** User balance after this operation. */
    #[Column(type: "float")]
    public float $balanceAfter;

    /** Module or service that initiated the operation (e.g. "shop", "clans", "admin"). */
    #[Column(type: "string", nullable: true)]
    public ?string $source = null;

    /** Human-readable description shown to the user. */
    #[Column(type: "string", nullable: true)]
    public ?string $description = null;

    /** Structured metadata serialized as JSON. */
    #[Column(type: "json", nullable: true)]
    public ?string $additional = null;

    #[Column(type: "datetime")]
    public DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getMeta(): BalanceHistoryMeta
    {
        return BalanceHistoryMeta::fromArray($this->getAdditional());
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getAdditional(): ?array
    {
        return $this->additional ? json_decode($this->additional, true) : null;
    }

    /**
     * @param array<string, mixed>|null $data
     */
    public function setAdditional(?array $data): void
    {
        $this->additional = $data ? json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
    }

    public function isDebit(): bool
    {
        return $this->amount < 0;
    }

    public function isCredit(): bool
    {
        return $this->amount > 0;
    }
}
