<?php

namespace Flute\Admin\Packages\Dashboard\Services;

use Carbon\Carbon;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Fields\Tab;
use Flute\Admin\Platform\Layouts\Chart;
use Illuminate\Support\Collection;
use Flute\Core\Database\Entities\User;
use Flute\Core\Database\Entities\Notification;
use DateTimeImmutable;
use Flute\Core\Database\Entities\PaymentInvoice;

/**
 * Service class for handling dashboard layouts and tabs
 */
class DashboardService
{
    /**
     * Collection of dashboard tabs
     *
     * @var Collection
     */
    protected Collection $tabs;

    protected Collection $vars;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->tabs = collect([]);
        $this->vars = collect([]);
        $this->registerDefaultTabs();
    }

    /**
     * Calculate user metrics
     *
     * @return array
     */
    protected function calculateUserMetrics(): array
    {
        $now = new DateTimeImmutable();
        $today = $now->setTime(0, 0);
        $yesterday = $today->modify('-1 day');
        $lastWeek = $today->modify('-7 days');

        // Get all users
        $users = User::query()->where('isTemporary', false)->fetchAll();
        $totalUsers = count($users);

        // Calculate metrics for current period
        $activeUsers = 0;
        $onlineUsers = 0;
        $newUsersToday = 0;

        // Calculate metrics for previous period
        $activeUsersYesterday = 0;
        $onlineUsersLastWeek = 0;
        $newUsersYesterday = 0;
        $totalUsersYesterday = 0;

        foreach ($users as $user) {
            // Current period calculations
            if ($user->last_logged && $user->isOnline()) {
                $onlineUsers++;
            }

            if ($user->last_logged && $user->last_logged > $today) {
                $activeUsers++;
            }

            if ($user->createdAt > $today) {
                $newUsersToday++;
            }

            // Previous period calculations
            if ($user->last_logged && $user->last_logged > $yesterday && $user->last_logged <= $today) {
                $activeUsersYesterday++;
            }

            if ($user->createdAt > $yesterday && $user->createdAt <= $today) {
                $newUsersYesterday++;
            }

            if ($user->createdAt <= $yesterday) {
                $totalUsersYesterday++;
            }

            // Calculate online users from last week (for better diff perspective)
            if ($user->last_logged && $user->last_logged > $lastWeek) {
                $onlineUsersLastWeek++;
            }
        }

        // Calculate percentage differences
        $totalUsersDiff = $totalUsersYesterday > 0
            ? (($totalUsers - $totalUsersYesterday) / $totalUsersYesterday) * 100
            : 100;

        $activeUsersDiff = $activeUsersYesterday > 0
            ? (($activeUsers - $activeUsersYesterday) / $activeUsersYesterday) * 100
            : ($activeUsers > 0 ? 100 : 0);

        $onlineUsersDiff = $onlineUsersLastWeek > 0
            ? (($onlineUsers - $onlineUsersLastWeek) / $onlineUsersLastWeek) * 100
            : ($onlineUsers > 0 ? 100 : 0);

        $newUsersDiff = $newUsersYesterday > 0
            ? (($newUsersToday - $newUsersYesterday) / $newUsersYesterday) * 100
            : ($newUsersToday > 0 ? 100 : 0);

        return [
            'total_users' => [
                'value' => number_format($totalUsers),
                'diff' => round($totalUsersDiff, 1)
            ],
            'active_users' => [
                'value' => number_format($activeUsers),
                'diff' => round($activeUsersDiff, 1)
            ],
            'online_users' => [
                'value' => number_format($onlineUsers),
                'diff' => round($onlineUsersDiff, 1)
            ],
            'new_users_today' => [
                'value' => number_format($newUsersToday),
                'diff' => round($newUsersDiff, 1)
            ],
        ];
    }

    /**
     * Calculate notification metrics
     *
     * @return array
     */
    protected function calculateNotificationMetrics(): array
    {
        $now = new DateTimeImmutable();
        $today = $now->setTime(0, 0);

        // Get all notifications
        $notifications = Notification::query()->where('isRead', false)->fetchAll();
        $totalNotifications = count($notifications);

        $unreadNotifications = 0;
        $actionsToday = 0;
        $activeSessions = 0;

        foreach ($notifications as $notification) {
            if (!$notification->viewed) {
                $unreadNotifications++;
            }

            if ($notification->createdAt > $today) {
                $actionsToday++;
            }
        }

        // Get active sessions from online users
        $users = User::query()->where('isTemporary', false)->fetchAll();
        foreach ($users as $user) {
            if ($user->isOnline()) {
                $activeSessions++;
            }
        }

        return [
            'total_notifications' => $totalNotifications,
            'unread_notifications' => $unreadNotifications,
            'actions_today' => $actionsToday,
            'active_sessions' => $activeSessions,
        ];
    }

    /**
     * Calculate user registration chart data
     *
     * @return array
     */
    protected function calculateUserRegistrationData(): array
    {
        $now = new DateTimeImmutable();
        $startDate = $now->modify('-8 months');
        $users = User::query()->where('isTemporary', false)->fetchAll();

        $monthlyRegistrations = array_fill(0, 9, 0);
        $labels = [];

        // Generate labels for last 9 months
        for ($i = 0; $i < 9; $i++) {
            $date = $startDate->modify('+' . $i . ' month');
            $carbonDate = Carbon::parse($date);
            $labels[] = $carbonDate->translatedFormat('M');
        }

        foreach ($users as $user) {
            $monthDiff = $user->createdAt->diff($startDate)->m;
            if ($monthDiff >= 0 && $monthDiff < 9) {
                $monthlyRegistrations[$monthDiff]++;
            }
        }

        return [
            'series' => [
                [
                    'name' => "New Users",
                    'data' => $monthlyRegistrations
                ]
            ],
            'labels' => $labels
        ];
    }

    /**
     * Calculate user activity chart data
     *
     * @return array
     */
    protected function calculateUserActivityData(): array
    {
        $now = new DateTimeImmutable();
        $startDate = $now->modify('-6 days');
        $users = User::query()->where('isTemporary', false)->fetchAll();

        $dailyActive = array_fill(0, 7, 0);
        $dailyOnline = array_fill(0, 7, 0);
        $labels = [];

        for ($i = 0; $i < 7; $i++) {
            $date = $startDate->modify('+' . $i . ' day');
            $carbonDate = Carbon::parse($date);
            $labels[] = $carbonDate->translatedFormat('D');
        }

        foreach ($users as $user) {
            if ($user->last_logged) {
                $dayDiff = $user->last_logged->diff($startDate)->d;
                if ($dayDiff >= 0 && $dayDiff < 7) {
                    $dailyActive[$dayDiff]++;
                    if ($user->isOnline()) {
                        $dailyOnline[$dayDiff]++;
                    }
                }
            }
        }

        return [
            'series' => [
                [
                    'name' => "Active Users",
                    'data' => $dailyActive
                ],
                [
                    'name' => "Online Users",
                    'data' => $dailyOnline
                ]
            ],
            'labels' => $labels
        ];
    }

    /**
     * Calculate payment metrics
     *
     * @return array
     */
    protected function calculatePaymentMetrics(): array
    {
        $now = new DateTimeImmutable();
        $today = $now->setTime(0, 0);
        $yesterday = $today->modify('-1 day');
        $lastMonth = $today->modify('-30 days');

        // Get all invoices
        $invoices = PaymentInvoice::query()->where('isPaid', true)->fetchAll();

        // Current period metrics
        $totalRevenue = 0;
        $todayRevenue = 0;
        $successfulPayments = 0;
        $promoUsage = 0;

        // Previous period metrics
        $yesterdayRevenue = 0;
        $lastMonthRevenue = 0;
        $yesterdayPayments = 0;
        $lastMonthPromoUsage = 0;

        foreach ($invoices as $invoice) {
            if ($invoice->isPaid) {
                $totalRevenue += $invoice->amount;
                $successfulPayments++;

                if ($invoice->paidAt > $today) {
                    $todayRevenue += $invoice->amount;
                }

                if ($invoice->paidAt > $yesterday && $invoice->paidAt <= $today) {
                    $yesterdayRevenue += $invoice->amount;
                    $yesterdayPayments++;
                }

                if ($invoice->paidAt > $lastMonth) {
                    $lastMonthRevenue += $invoice->amount;
                }

                if ($invoice->promoCode) {
                    if ($invoice->paidAt > $today) {
                        $promoUsage++;
                    }
                    if ($invoice->paidAt > $lastMonth) {
                        $lastMonthPromoUsage++;
                    }
                }
            }
        }

        // Calculate percentage differences
        $revenueDiff = $yesterdayRevenue > 0
            ? (($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100
            : ($todayRevenue > 0 ? 100 : 0);

        $monthlyRevenueDiff = $lastMonthRevenue > 0
            ? (($totalRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100
            : 100;

        $paymentsDiff = $yesterdayPayments > 0
            ? (($successfulPayments - $yesterdayPayments) / $yesterdayPayments) * 100
            : ($successfulPayments > 0 ? 100 : 0);

        $promoUsageDiff = $lastMonthPromoUsage > 0
            ? (($promoUsage - $lastMonthPromoUsage) / $lastMonthPromoUsage) * 100
            : ($promoUsage > 0 ? 100 : 0);

        return [
            'total_revenue' => [
                'value' => number_format($totalRevenue, 2) . ' ' . config('lk.currency_view'),
                'diff' => round($monthlyRevenueDiff, 1)
            ],
            'today_revenue' => [
                'value' => number_format($todayRevenue, 2) . ' ' . config('lk.currency_view'),
                'diff' => round($revenueDiff, 1)
            ],
            'successful_payments' => [
                'value' => number_format($successfulPayments),
                'diff' => round($paymentsDiff, 1)
            ],
            'promo_usage' => [
                'value' => number_format($promoUsage),
                'diff' => round($promoUsageDiff, 1)
            ],
        ];
    }

    /**
     * Calculate payment statistics for charts
     *
     * @return array
     */
    protected function calculatePaymentChartData(): array
    {
        $now = new DateTimeImmutable();
        $startDate = $now->modify('-6 days');

        $invoices = PaymentInvoice::query()->where('isPaid', true)->fetchAll();

        $dailyRevenue = array_fill(0, 7, 0);
        $dailyPayments = array_fill(0, 7, 0);
        $labels = [];

        // Generate labels for last 7 days using Carbon
        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::now()->subDays($i)->translatedFormat('D');
            $labels[] = $date;
        }

        foreach ($invoices as $invoice) {
            if ($invoice->isPaid && $invoice->paidAt) {
                $dayDiff = $invoice->paidAt->diff($startDate)->d;
                if ($dayDiff >= 0 && $dayDiff < 7) {
                    $dailyRevenue[$dayDiff] += $invoice->amount;
                    $dailyPayments[$dayDiff]++;
                }
            }
        }

        return [
            'series' => [
                [
                    'name' => "Daily Revenue",
                    'data' => $dailyRevenue
                ],
                [
                    'name' => "Daily Payments",
                    'data' => $dailyPayments
                ]
            ],
            'labels' => $labels
        ];
    }

    /**
     * Calculate payment methods distribution
     *
     * @return array
     */
    protected function calculatePaymentMethodsData(): array
    {
        $invoices = PaymentInvoice::query()->where('isPaid', true)->fetchAll();
        $gateways = [];

        foreach ($invoices as $invoice) {
            if ($invoice->isPaid) {
                if (!isset($gateways[$invoice->gateway])) {
                    $gateways[$invoice->gateway] = 0;
                }
                $gateways[$invoice->gateway]++;
            }
        }

        $data = [];
        $labels = [];
        foreach ($gateways as $gateway => $count) {
            $data[] = $count;
            $labels[] = $gateway;
        }

        return [
            'series' => $data,
            'labels' => $labels
        ];
    }

    /**
     * Get the main information tab
     *
     * @return array
     */
    protected function getMainTab(): array
    {
        $userMetrics = $this->calculateUserMetrics();
        $userRegistrationData = $this->calculateUserRegistrationData();
        $userActivityData = $this->calculateUserActivityData();

        $metrics = LayoutFactory::metrics([
            'admin-dashboard.metrics.total_users' => 'vars.total_users',
            'admin-dashboard.metrics.active_users' => 'vars.active_users',
            'admin-dashboard.metrics.online_users' => 'vars.online_users',
            'admin-dashboard.metrics.new_users_today' => 'vars.new_users_today',
        ])->setIcons([
            'admin-dashboard.metrics.total_users' => 'users-three',
            'admin-dashboard.metrics.active_users' => 'user-circle',
            'admin-dashboard.metrics.online_users' => 'user-focus',
            'admin-dashboard.metrics.new_users_today' => 'user-plus',
        ]);

        return [
            'tab' => Tab::make(__('admin-dashboard.tabs.main'))
                ->icon('ph.regular.users')
                ->layouts([
                    $metrics,
                    LayoutFactory::split([
                        Chart::make('user_registrations', __('admin-dashboard.charts.user_registrations'))
                            ->type('area')
                            ->height(300)
                            ->colors(['var(--accent)', 'var(--accent-400)'])
                            ->description(__('admin-dashboard.descriptions.user_registrations'))
                            ->dataset($userRegistrationData['series'])
                            ->labels($userRegistrationData['labels']),
                        Chart::make('user_activity', __('admin-dashboard.charts.user_activity'))
                            ->type('area')
                            ->height(300)
                            ->colors(['var(--success)', 'var(--warning)'])
                            ->description(__('admin-dashboard.descriptions.user_activity'))
                            ->dataset($userActivityData['series'])
                            ->labels($userActivityData['labels']),
                    ])
                ]),
            'vars' => $userMetrics
        ];
    }

    /**
     * Get the payments tab
     *
     * @return array
     */
    protected function getPaymentsTab(): array
    {
        $paymentMetrics = $this->calculatePaymentMetrics();
        $paymentChartData = $this->calculatePaymentChartData();
        $paymentMethodsData = $this->calculatePaymentMethodsData();

        $metrics = LayoutFactory::metrics([
            'admin-dashboard.metrics.total_revenue' => 'vars.total_revenue',
            'admin-dashboard.metrics.today_revenue' => 'vars.today_revenue',
            'admin-dashboard.metrics.successful_payments' => 'vars.successful_payments',
            'admin-dashboard.metrics.promo_usage' => 'vars.promo_usage',
        ])->setIcons([
            'admin-dashboard.metrics.total_revenue' => 'money',
            'admin-dashboard.metrics.today_revenue' => 'currency-circle-dollar',
            'admin-dashboard.metrics.successful_payments' => 'check-circle',
            'admin-dashboard.metrics.promo_usage' => 'ticket',
        ]);

        return [
            'tab' => Tab::make(__('admin-dashboard.tabs.payments'))
                ->icon('ph.regular.currency-circle-dollar')
                ->layouts([
                    $metrics,
                    LayoutFactory::split([
                        Chart::make('payment_stats', __('admin-dashboard.charts.payment_stats'))
                            ->type('area')
                            ->height(300)
                            ->colors(['var(--success)', 'var(--primary)'])
                            ->description(__('admin-dashboard.descriptions.payment_stats'))
                            ->dataset($paymentChartData['series'])
                            ->labels($paymentChartData['labels']),
                        Chart::make('payment_methods', __('admin-dashboard.charts.payment_methods'))
                            ->type('donut')
                            ->height(300)
                            ->colors(['var(--accent-500)', 'var(--primary-500)', 'var(--success-light)', 'var(--warning-light)'])
                            ->description(__('admin-dashboard.descriptions.payment_methods'))
                            ->dataset($paymentMethodsData['series'])
                            ->labels($paymentMethodsData['labels']),
                    ])
                ]),
            'vars' => $paymentMetrics
        ];
    }

    /**
     * Register default dashboard tabs
     *
     * @return void
     */
    protected function registerDefaultTabs(): void
    {
        $mainTab = $this->getMainTab();
        $this->addTab($mainTab['tab'], $mainTab['vars']);

        $paymentsTab = $this->getPaymentsTab();
        $this->addTab($paymentsTab['tab'], $paymentsTab['vars']);
    }

    /**
     * Add a new tab to the dashboard
     *
     * @param Tab $tab
     * @param array $vars
     * @return self
     */
    public function addTab(Tab $tab, array $vars = []): self
    {
        $this->tabs->push($tab);
        $this->vars = $this->vars->merge($vars);
        return $this;
    }

    /**
     * Get all registered tabs
     *
     * @return Collection
     */
    public function getTabs(): Collection
    {
        return $this->tabs;
    }

    /**
     * Get all registered vars
     *
     * @return Collection
     */
    public function getVars(): Collection
    {
        return $this->vars;
    }

    /**
     * Get the main dashboard layout with tabs
     *
     * @return array
     */
    public function getLayout(): array
    {
        return [
            LayoutFactory::tabs($this->tabs->all())->pills()->lazyload(false)
        ];
    }
}
