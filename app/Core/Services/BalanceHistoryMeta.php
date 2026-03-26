<?php

namespace Flute\Core\Services;

/**
 * Typed metadata for balance history entries.
 *
 * Usage:
 *   $meta = BalanceHistoryMeta::make()->itemId(42)->itemName('VIP')->url('/shop/vip');
 *   userService->unbalance(100, $user, 'shop', 'VIP purchase', $meta);
 */
final class BalanceHistoryMeta
{
    /**
     * @param array<string, mixed> $data
     */
    private function __construct(
        private array $data = [],
    ) {
    }

    public static function make(): self
    {
        return new self();
    }

    /**
     * Create from a raw array (e.g. when reading from DB).
     *
     * @param array<string, mixed>|null $data
     */
    public static function fromArray(?array $data): self
    {
        return new self($data ?? []);
    }

    /** Item / entity ID the operation relates to. */
    public function itemId(int|string $id): self
    {
        $this->data['item_id'] = $id;

        return $this;
    }

    /** Human-readable item / entity name. */
    public function itemName(string $name): self
    {
        $this->data['item_name'] = $name;

        return $this;
    }

    /** URL to the related item or page. */
    public function url(string $url): self
    {
        $this->data['url'] = $url;

        return $this;
    }

    /** Game server ID the operation relates to. */
    public function serverId(int $id): self
    {
        $this->data['server_id'] = $id;

        return $this;
    }

    /** Related payment invoice ID. */
    public function invoiceId(int $id): self
    {
        $this->data['invoice_id'] = $id;

        return $this;
    }

    /** Promo code ID applied to this operation. */
    public function promoCodeId(int $id): self
    {
        $this->data['promo_code_id'] = $id;

        return $this;
    }

    /**
     * Discount applied to this operation.
     *
     * @param 'fixed'|'percentage' $type
     */
    public function discount(float $value, string $type = 'fixed'): self
    {
        $this->data['discount'] = ['value' => $value, 'type' => $type];

        return $this;
    }

    /** Set an arbitrary metadata field. */
    public function set(string $key, mixed $value): self
    {
        $this->data[$key] = $value;

        return $this;
    }

    /** Get a metadata field value. */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    public function isEmpty(): bool
    {
        return $this->data === [];
    }
}
