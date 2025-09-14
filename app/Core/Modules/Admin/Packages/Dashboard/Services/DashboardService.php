<?php

namespace Flute\Admin\Packages\Dashboard\Services;

use Carbon\Carbon;
use DateTimeImmutable;
use Flute\Admin\Platform\Fields\Tab;
use Flute\Admin\Platform\Layouts\Chart;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Core\Database\Entities\Notification;
use Flute\Core\Database\Entities\PaymentInvoice;
use Flute\Core\Database\Entities\User;
use Illuminate\Support\Collection;

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

        // Total users
        $totalUsers = User::query()->where('isTemporary', false)->count();

        // Total users yesterday (users created up to end of yesterday)
        $totalUsersYesterday = User::query()
            ->where('isTemporary', false)
            ->where('createdAt', '<=', $yesterday)
            ->count();

        // New users today
        $newUsersToday = User::query()
            ->where('isTemporary', false)
            ->where('createdAt', '>', $today)
            ->count();

        // New users yesterday
        $newUsersYesterday = User::query()
            ->where('isTemporary', false)
            ->where('createdAt', '>', $yesterday)
            ->where('createdAt', '<=', $today)
            ->count();

        // Active users today (logged since start of today)
        $activeUsers = User::query()
            ->where('isTemporary', false)
            ->where('last_logged', '>', $today)
            ->count();

        // Active users yesterday
        $activeUsersYesterday = User::query()
            ->where('isTemporary', false)
            ->where('last_logged', '>', $yesterday)
            ->where('last_logged', '<=', $today)
            ->count();

        // Online users now: last_logged within 10 minutes
        $onlineThreshold = (new DateTimeImmutable('-10 minutes'));
        $onlineUsers = User::query()
            ->where('isTemporary', false)
            ->where('last_logged', '>=', $onlineThreshold)
            ->count();

        // Online last week reference (logged since last week)
        $onlineUsersLastWeek = User::query()
            ->where('isTemporary', false)
            ->where('last_logged', '>=', $lastWeek)
            ->count();

        $totalUsersDiff = $totalUsersYesterday > 0
            ? (($totalUsers - $totalUsersYesterday) / max($totalUsersYesterday, 1)) * 100
            : 0;

        $activeUsersDiff = $activeUsersYesterday > 0
            ? (($activeUsers - $activeUsersYesterday) / max($activeUsersYesterday, 1)) * 100
            : 0;

        $onlineUsersDiff = $onlineUsersLastWeek > 0
            ? (($onlineUsers - $onlineUsersLastWeek) / max($onlineUsersLastWeek, 1)) * 100
            : 0;

        $newUsersDiff = $newUsersYesterday > 0
            ? (($newUsersToday - $newUsersYesterday) / max($newUsersYesterday, 1)) * 100
            : 0;

        return [
            'total_users' => [
                'value' => number_format($totalUsers),
                'diff' => round($totalUsersDiff, 1),
            ],
            'active_users' => [
                'value' => number_format($activeUsers),
                'diff' => round($activeUsersDiff, 1),
            ],
            'online_users' => [
                'value' => number_format($onlineUsers),
                'diff' => round($onlineUsersDiff, 1),
            ],
            'new_users_today' => [
                'value' => number_format($newUsersToday),
                'diff' => round($newUsersDiff, 1),
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

        // Total unread notifications
        $unreadNotifications = Notification::query()
            ->where('viewed', false)
            ->count();

        // Actions today: notifications created today
        $actionsToday = Notification::query()
            ->where('createdAt', '>', $today)
            ->count();

        // Active sessions ~ users online now (10 min)
        $onlineThreshold = (new DateTimeImmutable('-10 minutes'));
        $activeSessions = User::query()
            ->where('isTemporary', false)
            ->where('last_logged', '>=', $onlineThreshold)
            ->count();

        return [
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
        $users = User::query()
            ->where('isTemporary', false)
            ->where('createdAt', '>=', $startDate)
            ->fetchAll();

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
                    'data' => $monthlyRegistrations,
                ],
            ],
            'labels' => $labels,
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
        $users = User::query()
            ->where('isTemporary', false)
            ->where('last_logged', '>=', $startDate)
            ->fetchAll();

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
                    'data' => $dailyActive,
                ],
                [
                    'name' => "Online Users",
                    'data' => $dailyOnline,
                ],
            ],
            'labels' => $labels,
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

        // Successful payments count (lifetime)
        $successfulPayments = PaymentInvoice::query()
            ->where('isPaid', true)
            ->count();

        // Yesterday payments count
        $yesterdayPayments = PaymentInvoice::query()
            ->where('isPaid', true)
            ->where('paidAt', '>', $yesterday)
            ->where('paidAt', '<=', $today)
            ->count();

        // Promo usage today
        $promoUsage = PaymentInvoice::query()
            ->where('isPaid', true)
            ->where('paidAt', '>', $today)
            ->where('promoCode_id', 'is not', null)
            ->count();

        // Promo usage last month
        $lastMonthPromoUsage = PaymentInvoice::query()
            ->where('isPaid', true)
            ->where('paidAt', '>', $lastMonth)
            ->where('promoCode_id', 'is not', null)
            ->count();

        // Total revenue (lifetime)
        $totalRevenueQuery = PaymentInvoice::query()
            ->where('isPaid', true)
            ->buildQuery();
        $totalRevenueQuery->columns([new \Cycle\Database\Injection\Fragment('COALESCE(SUM(amount),0) as sum')]);
        $totalRevenue = $totalRevenueQuery->limit(1)->fetchAll()[0]['sum'] ?? 0;

        // Today revenue
        $todayRevenueQuery = PaymentInvoice::query()
            ->where('isPaid', true)
            ->where('paidAt', '>', $today)
            ->buildQuery();
        $todayRevenueQuery->columns([new \Cycle\Database\Injection\Fragment('COALESCE(SUM(amount),0) as sum')]);
        $todayRevenue = $todayRevenueQuery->limit(1)->fetchAll()[0]['sum'] ?? 0;

        // Yesterday revenue
        $yesterdayRevenueQuery = PaymentInvoice::query()
            ->where('isPaid', true)
            ->where('paidAt', '>', $yesterday)
            ->where('paidAt', '<=', $today)
            ->buildQuery();
        $yesterdayRevenueQuery->columns([new \Cycle\Database\Injection\Fragment('COALESCE(SUM(amount),0) as sum')]);
        $yesterdayRevenue = $yesterdayRevenueQuery->limit(1)->fetchAll()[0]['sum'] ?? 0;

        // Last month revenue
        $lastMonthRevenueQuery = PaymentInvoice::query()
            ->where('isPaid', true)
            ->where('paidAt', '>', $lastMonth)
            ->buildQuery();
        $lastMonthRevenueQuery->columns([new \Cycle\Database\Injection\Fragment('COALESCE(SUM(amount),0) as sum')]);
        $lastMonthRevenue = $lastMonthRevenueQuery->limit(1)->fetchAll()[0]['sum'] ?? 0;

        // Calculate percentage differences
        $revenueDiff = $yesterdayRevenue > 0
            ? (($todayRevenue - $yesterdayRevenue) / max($yesterdayRevenue, 1)) * 100
            : 0;

        $monthlyRevenueDiff = $lastMonthRevenue > 0
            ? (($totalRevenue - $lastMonthRevenue) / max($lastMonthRevenue, 1)) * 100
            : 0;

        $paymentsDiff = $yesterdayPayments > 0
            ? (($successfulPayments - $yesterdayPayments) / max($yesterdayPayments, 1)) * 100
            : 0;

        $promoUsageDiff = $lastMonthPromoUsage > 0
            ? (($promoUsage - $lastMonthPromoUsage) / max($lastMonthPromoUsage, 1)) * 100
            : 0;

        return [
            'total_revenue' => [
                'value' => number_format($totalRevenue, 2) . ' ' . config('lk.currency_view'),
                'diff' => round($monthlyRevenueDiff, 1),
            ],
            'today_revenue' => [
                'value' => number_format($todayRevenue, 2) . ' ' . config('lk.currency_view'),
                'diff' => round($revenueDiff, 1),
            ],
            'successful_payments' => [
                'value' => number_format($successfulPayments),
                'diff' => round($paymentsDiff, 1),
            ],
            'promo_usage' => [
                'value' => number_format($promoUsage),
                'diff' => round($promoUsageDiff, 1),
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
        $startDate = $now->modify('-6 days')->setTime(0, 0);

        $dailyRevenue = array_fill(0, 7, 0);
        $dailyPayments = array_fill(0, 7, 0);
        $labels = [];

        // Pre-build 7 day windows and labels
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $dayStart = $startDate->modify("+{$i} day");
            $dayEnd = $dayStart->modify('+1 day');
            $days[] = [$dayStart, $dayEnd];
            $labels[] = Carbon::parse($dayStart)->translatedFormat('D');
        }

        // Query per day using ORM aggregates (keeps memory stable)
        foreach ($days as $idx => [$dayStart, $dayEnd]) {
            $revenueQuery = PaymentInvoice::query()
                ->where('isPaid', true)
                ->where('paidAt', '>=', $dayStart)
                ->where('paidAt', '<', $dayEnd)
                ->buildQuery();
            $revenueQuery->columns([new \Cycle\Database\Injection\Fragment('COALESCE(SUM(amount),0) as sum')]);
            $sum = $revenueQuery->limit(1)->fetchAll()[0]['sum'] ?? 0;

            $cnt = PaymentInvoice::query()
                ->where('isPaid', true)
                ->where('paidAt', '>=', $dayStart)
                ->where('paidAt', '<', $dayEnd)
                ->count();

            $dailyRevenue[$idx] = (float) $sum;
            $dailyPayments[$idx] = (int) $cnt;
        }

        return [
            'series' => [
                [
                    'name' => "Daily Revenue",
                    'data' => $dailyRevenue,
                ],
                [
                    'name' => "Daily Payments",
                    'data' => $dailyPayments,
                ],
            ],
            'labels' => $labels,
        ];
    }

    /**
     * Calculate payment methods distribution
     *
     * @return array
     */
    protected function calculatePaymentMethodsData(): array
    {
        $gatewayQuery = PaymentInvoice::query()
            ->where('isPaid', true)
            ->buildQuery();
        $gatewayQuery->columns([
            'gateway',
            new \Cycle\Database\Injection\Fragment('COUNT(*) as count'),
        ]);
        $gatewayQuery->groupBy('gateway');
        $gatewayResults = $gatewayQuery->fetchAll();

        $data = [];
        $labels = [];
        foreach ($gatewayResults as $result) {
            $data[] = (int) $result['count'];
            $labels[] = $result['gateway'];
        }

        return [
            'series' => $data,
            'labels' => $labels,
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
                    ]),
                ]),
            'vars' => $userMetrics,
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
                    ]),
                ]),
            'vars' => $paymentMetrics,
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
            LayoutFactory::tabs($this->tabs->all())->pills()->lazyload(false),
        ];
    }
}
