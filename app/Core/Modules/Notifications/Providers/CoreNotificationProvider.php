<?php

namespace Flute\Core\Modules\Notifications\Providers;

use Flute\Core\Modules\Notifications\Contracts\NotificationTemplateProviderInterface;

/**
 * Registers all core notification templates.
 */
class CoreNotificationProvider implements NotificationTemplateProviderInterface
{
    public function getModuleName(): string
    {
        return 'core';
    }

    public function getNotificationTemplates(): array
    {
        return [
            // ── Auth ──────────────────────────────
            [
                'key' => 'core.welcome',
                'title' => 'notification-templates.welcome.title',
                'content' => 'notification-templates.welcome.content',
                'icon' => 'ph.bold.hand-waving-bold',
                'channels' => ['inapp', 'email'],
                'variables' => [
                    'name' => 'notification-templates.vars.name',
                ],
                'priority' => 10,
            ],
            [
                'key' => 'core.new_device_login',
                'title' => 'notification-templates.new_device_login.title',
                'content' => 'notification-templates.new_device_login.content',
                'icon' => 'ph.bold.device-mobile-bold',
                'channels' => ['inapp', 'email'],
                'variables' => [
                    'ip' => 'notification-templates.vars.ip',
                    'device' => 'notification-templates.vars.device',
                    'time' => 'notification-templates.vars.time',
                ],
                'priority' => 20,
            ],
            [
                'key' => 'core.password_changed',
                'title' => 'notification-templates.password_changed.title',
                'content' => 'notification-templates.password_changed.content',
                'icon' => 'ph.bold.lock-bold',
                'channels' => ['inapp', 'email'],
                'variables' => [
                    'time' => 'notification-templates.vars.time',
                ],
                'priority' => 30,
            ],

            // ── Payments ──────────────────────────
            [
                'key' => 'core.payment_success',
                'title' => 'notification-templates.payment_success.title',
                'content' => 'notification-templates.payment_success.content',
                'icon' => 'ph.bold.check-circle-bold',
                'channels' => ['inapp', 'email'],
                'variables' => [
                    'amount' => 'notification-templates.vars.amount',
                    'gateway' => 'notification-templates.vars.gateway',
                    'transaction_id' => 'notification-templates.vars.transaction_id',
                ],
                'components' => [
                    [
                        'type' => 'actions',
                        'buttons' => [
                            [
                                'label' => 'notification-templates.payment_success.view_history',
                                'type' => 'primary',
                                'url' => '/profile/settings#payments',
                            ],
                        ],
                    ],
                ],
                'priority' => 40,
            ],
            [
                'key' => 'core.balance_topup',
                'title' => 'notification-templates.balance_topup.title',
                'content' => 'notification-templates.balance_topup.content',
                'icon' => 'ph.bold.wallet-bold',
                'channels' => ['inapp'],
                'variables' => [
                    'amount' => 'notification-templates.vars.amount',
                    'balance' => 'notification-templates.vars.balance',
                ],
                'priority' => 50,
            ],
            [
                'key' => 'core.invoice_created',
                'title' => 'notification-templates.invoice_created.title',
                'content' => 'notification-templates.invoice_created.content',
                'icon' => 'ph.bold.receipt-bold',
                'channels' => ['inapp'],
                'variables' => [
                    'amount' => 'notification-templates.vars.amount',
                    'gateway' => 'notification-templates.vars.gateway',
                    'transaction_id' => 'notification-templates.vars.transaction_id',
                ],
                'components' => [
                    [
                        'type' => 'actions',
                        'buttons' => [
                            [
                                'label' => 'notification-templates.invoice_created.pay_now',
                                'type' => 'primary',
                                'url' => '/payment/{transaction_id}',
                                'target' => '_blank',
                            ],
                        ],
                    ],
                ],
                'priority' => 45,
            ],

            // ── Profile / Account ─────────────────
            [
                'key' => 'core.email_verified',
                'title' => 'notification-templates.email_verified.title',
                'content' => 'notification-templates.email_verified.content',
                'icon' => 'ph.bold.seal-check-bold',
                'channels' => ['inapp'],
                'variables' => [],
                'priority' => 60,
            ],
        ];
    }
}
