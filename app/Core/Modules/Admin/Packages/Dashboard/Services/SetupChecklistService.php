<?php

namespace Flute\Admin\Packages\Dashboard\Services;

use Flute\Core\Database\Entities\Currency;
use Flute\Core\Database\Entities\PaymentGateway;
use Flute\Core\Database\Entities\Server;
use Flute\Core\Database\Entities\SocialNetwork;

class SetupChecklistService
{
    protected array $items = [];

    public function getItems(): array
    {
        if (empty($this->items)) {
            $this->buildItems();
        }

        return $this->items;
    }

    public function getCompletedCount(): int
    {
        return count(array_filter($this->getItems(), static fn (array $item) => $item['done']));
    }

    public function getTotalCount(): int
    {
        return count($this->getItems());
    }

    public function isAllDone(): bool
    {
        return $this->getCompletedCount() === $this->getTotalCount();
    }

    public function getProgressPercent(): int
    {
        $total = $this->getTotalCount();

        return $total > 0 ? (int) round(($this->getCompletedCount() / $total) * 100) : 0;
    }

    protected function buildItems(): void
    {
        $this->items = [
            [
                'key' => 'logo',
                'done' => $this->isLogoConfigured(),
                'url' => url('/admin/main-settings') . '?tab-settings=general&tab-main_settings_sections=branding',
            ],
            [
                'key' => 'smtp',
                'done' => $this->isSmtpConfigured(),
                'url' => url('/admin/main-settings') . '?tab-settings=mail',
            ],
            [
                'key' => 'social',
                'done' => $this->isSocialConfigured(),
                'url' => url('/admin/socials'),
            ],
            [
                'key' => 'server',
                'done' => $this->isServerAdded(),
                'url' => url('/admin/servers/add'),
            ],
            [
                'key' => 'currency',
                'done' => $this->isCurrencyConfigured(),
                'url' => url('/admin/currency'),
            ],
            [
                'key' => 'payment',
                'done' => $this->isPaymentConfigured(),
                'url' => url('/admin/payment/gateways'),
            ],
        ];
    }

    protected function isLogoConfigured(): bool
    {
        $logo = config('app.logo', '');

        return $logo !== '' && !str_ends_with($logo, 'logo.svg');
    }

    protected function isSmtpConfigured(): bool
    {
        return (bool) config('mail.smtp') && config('mail.host') !== '';
    }

    protected function isSocialConfigured(): bool
    {
        return SocialNetwork::query()->where('enabled', true)->count() > 0;
    }

    protected function isServerAdded(): bool
    {
        return Server::query()->count() > 0;
    }

    protected function isCurrencyConfigured(): bool
    {
        return Currency::query()->count() > 0;
    }

    protected function isPaymentConfigured(): bool
    {
        return PaymentGateway::query()->where('enabled', true)->count() > 0;
    }
}
