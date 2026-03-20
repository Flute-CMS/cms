<?php

namespace Flute\Admin\Packages\Dashboard\Services;

use Carbon\Carbon;
use DateTimeImmutable;
use DateTimeZone;
use Flute\Admin\Platform\Fields\Tab;
use Flute\Admin\Platform\Layouts\Chart;
use Flute\Admin\Platform\Layouts\Filters;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Core\Database\Entities\PaymentInvoice;
use Flute\Core\Database\Entities\User;
use Illuminate\Support\Collection;

/**
 * Service class for handling dashboard layouts and tabs
 */
class DashboardService
{
    /**
     * Available payment periods
     */
    public const PAYMENT_PERIODS = [
        '7d' => 7,
        '30d' => 30,
        '90d' => 90,
        '180d' => 180,
        '365d' => 365,
        'all' => null,
    ];

    /**
     * Collection of dashboard tabs
     */
    protected Collection $tabs;

    protected Collection $vars;

    /**
     * Current payment period
     */
    protected string $paymentPeriod = '7d';

    /**
     * Current user registration period
     */
    protected string $userPeriod = '90d';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->tabs = collect([]);
        $this->vars = collect([]);
        $this->paymentPeriod = request()->input('payment_period', '7d');
        $this->userPeriod = request()->input('user_period', '90d');
        $this->registerDefaultTabs();
    }

    /**
     * Set the payment period
     */
    public function setPaymentPeriod(string $period): self
    {
        if (array_key_exists($period, self::PAYMENT_PERIODS)) {
            $this->paymentPeriod = $period;
        }

        return $this;
    }

    /**
     * Get the current payment period
     */
    public function getPaymentPeriod(): string
    {
        return $this->paymentPeriod;
    }

    /**
     * Add a new tab to the dashboard
     */
    public function addTab(Tab $tab, array $vars = []): self
    {
        $this->tabs->push($tab);
        $this->vars = $this->vars->merge($vars);

        return $this;
    }

    /**
     * Get all registered tabs
     */
    public function getTabs(): Collection
    {
        return $this->tabs;
    }

    /**
     * Get all registered vars
     */
    public function getVars(): Collection
    {
        return $this->vars;
    }

    /**
     * Get the main dashboard layout with tabs
     */
    public function getLayout(): array
    {
        return [
            LayoutFactory::tabs($this->tabs->all())->pills()->lazyload(false),
        ];
    }

    /**
     * Get days count for current payment period
     */
    protected function getPeriodDays(): ?int
    {
        return self::PAYMENT_PERIODS[$this->paymentPeriod] ?? null;
    }

    /**
     * Get days count for current user period
     */
    protected function getUserPeriodDays(): ?int
    {
        return self::PAYMENT_PERIODS[$this->userPeriod] ?? null;
    }

    /**
     * Calculate user metrics
     */
    protected function calculateUserMetrics(): array
    {
        return cache()->callback('admin_dashboard_user_metrics', fn() => $this->doCalculateUserMetrics(), 120);
    }

    protected function doCalculateUserMetrics(): array
    {
        $appTz = new DateTimeZone(config('app.timezone', 'UTC'));
        $dbTz = new DateTimeZone('UTC');

        $now = new DateTimeImmutable('now', $appTz);
        $today = $now->setTime(0, 0);
        $yesterday = $today->modify('-1 day');
        $lastWeek = $today->modify('-7 days');

        $todayDb = $today->setTimezone($dbTz);
        $yesterdayDb = $yesterday->setTimezone($dbTz);
        $lastWeekDb = $lastWeek->setTimezone($dbTz);

        // Total users
        $totalUsers = User::query()->where('isTemporary', false)->count();

        // Total users yesterday (users created up to end of yesterday)
        $totalUsersYesterday = User::query()
            ->where('isTemporary', false)
            ->where('createdAt', '<=', $yesterdayDb)
            ->count();

        // New users today
        $newUsersToday = User::query()
            ->where('isTemporary', false)
            ->where('createdAt', '>', $todayDb)
            ->count();

        // New users yesterday
        $newUsersYesterday = User::query()
            ->where('isTemporary', false)
            ->where('createdAt', '>', $yesterdayDb)
            ->where('createdAt', '<=', $todayDb)
            ->count();

        // Active users today (logged since start of today)
        $activeUsers = User::query()
            ->where('isTemporary', false)
            ->where('last_logged', '>', $todayDb)
            ->count();

        // Active users yesterday
        $activeUsersYesterday = User::query()
            ->where('isTemporary', false)
            ->where('last_logged', '>', $yesterdayDb)
            ->where('last_logged', '<=', $todayDb)
            ->count();

        // Online users now: last_logged within 10 minutes
        $onlineThreshold = new DateTimeImmutable('-10 minutes', $appTz);
        $onlineThresholdDb = $onlineThreshold->setTimezone($dbTz);
        $onlineUsers = User::query()
            ->where('isTemporary', false)
            ->where('last_logged', '>=', $onlineThresholdDb)
            ->count();

        // Online last week reference (logged since last week)
        $onlineUsersLastWeek = User::query()
            ->where('isTemporary', false)
            ->where('last_logged', '>=', $lastWeekDb)
            ->count();

        $totalUsersDiff = $totalUsersYesterday > 0
            ? ( ( $totalUsers - $totalUsersYesterday ) / max($totalUsersYesterday, 1) ) * 100
            : 0;

        $activeUsersDiff = $activeUsersYesterday > 0
            ? ( ( $activeUsers - $activeUsersYesterday ) / max($activeUsersYesterday, 1) ) * 100
            : 0;

        $onlineUsersDiff = $onlineUsersLastWeek > 0
            ? ( ( $onlineUsers - $onlineUsersLastWeek ) / max($onlineUsersLastWeek, 1) ) * 100
            : 0;

        $newUsersDiff = $newUsersYesterday > 0
            ? ( ( $newUsersToday - $newUsersYesterday ) / max($newUsersYesterday, 1) ) * 100
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
     * Calculate user registration chart data
     */
    protected function calculateUserRegistrationData(): array
    {
        $cacheKey = 'admin_dashboard_user_registration_' . $this->userPeriod;

        return cache()->callback($cacheKey, fn() => $this->doCalculateUserRegistrationData(), 300);
    }

    protected function doCalculateUserRegistrationData(): array
    {
        $appTz = new DateTimeZone(config('app.timezone', 'UTC'));
        $dbTz = new DateTimeZone('UTC');

        $now = new DateTimeImmutable('now', $appTz);
        $periodDays = $this->getUserPeriodDays() ?? 365;

        // Determine grouping strategy based on period
        if ($periodDays <= 14) {
            $groupBy = 'day';
            $pointsCount = $periodDays;
            $dateFormat = 'D';
        } elseif ($periodDays <= 90) {
            $groupBy = 'week';
            $pointsCount = (int) ceil($periodDays / 7);
            $dateFormat = 'd M';
        } else {
            $groupBy = 'month';
            $pointsCount = (int) ceil($periodDays / 30);
            $dateFormat = 'M Y';
        }

        $registrations = [];
        $labels = [];

        if ($groupBy === 'day') {
            $startDate = $now->modify('-' . ( $periodDays - 1 ) . ' days')->setTime(0, 0);

            for ($i = 0; $i < $pointsCount; $i++) {
                $dayStart = $startDate->modify("+{$i} day");
                $dayEnd = $dayStart->modify('+1 day');
                $labels[] = Carbon::parse($dayStart)->translatedFormat($dateFormat);

                $dayStartDb = $dayStart->setTimezone($dbTz);
                $dayEndDb = $dayEnd->setTimezone($dbTz);

                $registrations[] = User::query()
                    ->where('isTemporary', false)
                    ->where('createdAt', '>=', $dayStartDb)
                    ->where('createdAt', '<', $dayEndDb)
                    ->count();
            }
        } elseif ($groupBy === 'week') {
            $startDate = $now->modify('-' . ( ( $pointsCount * 7 ) - 1 ) . ' days')->setTime(0, 0);

            for ($i = 0; $i < $pointsCount; $i++) {
                $weekStart = $startDate->modify('+' . ( $i * 7 ) . ' days');
                $weekEnd = $weekStart->modify('+7 days');
                $labels[] = Carbon::parse($weekStart)->translatedFormat($dateFormat);

                $weekStartDb = $weekStart->setTimezone($dbTz);
                $weekEndDb = $weekEnd->setTimezone($dbTz);

                $registrations[] = User::query()
                    ->where('isTemporary', false)
                    ->where('createdAt', '>=', $weekStartDb)
                    ->where('createdAt', '<', $weekEndDb)
                    ->count();
            }
        } else {
            $startDate = $now->modify('-' . $pointsCount . ' months');

            for ($i = 0; $i < $pointsCount; $i++) {
                $monthStart = $startDate->modify('+' . $i . ' month');
                $monthEnd = $monthStart->modify('+1 month');
                $labels[] = Carbon::parse($monthStart)->translatedFormat($dateFormat);

                $monthStartDb = $monthStart->setTimezone($dbTz);
                $monthEndDb = $monthEnd->setTimezone($dbTz);

                $registrations[] = User::query()
                    ->where('isTemporary', false)
                    ->where('createdAt', '>=', $monthStartDb)
                    ->where('createdAt', '<', $monthEndDb)
                    ->count();
            }
        }

        return [
            'series' => [
                [
                    'name' => __('admin-dashboard.charts.new_users'),
                    'data' => $registrations,
                ],
            ],
            'labels' => $labels,
        ];
    }

    /**
     * Calculate payment metrics
     */
    protected function calculatePaymentMetrics(): array
    {
        $cacheKey = 'admin_dashboard_payment_metrics_' . $this->paymentPeriod;

        return cache()->callback($cacheKey, fn() => $this->doCalculatePaymentMetrics(), 120);
    }

    protected function doCalculatePaymentMetrics(): array
    {
        $appTz = new DateTimeZone(config('app.timezone', 'UTC'));
        $dbTz = new DateTimeZone('UTC');

        $now = new DateTimeImmutable('now', $appTz);
        $today = $now->setTime(0, 0);
        $periodDays = $this->getPeriodDays();

        $todayDb = $today->setTimezone($dbTz);

        // Period start date
        $periodStart = $periodDays !== null ? $today->modify("-{$periodDays} days") : null;
        $periodStartDb = $periodStart ? $periodStart->setTimezone($dbTz) : null;

        // Previous period for comparison
        $prevPeriodStart = $periodDays !== null ? $today->modify('-' . ( $periodDays * 2 ) . ' days') : null;
        $prevPeriodStartDb = $prevPeriodStart ? $prevPeriodStart->setTimezone($dbTz) : null;

        // Build base query for period
        $baseQuery = PaymentInvoice::query()->where('isPaid', true);
        if ($periodStartDb) {
            $baseQuery->where('paidAt', '>=', $periodStartDb);
        }

        // Successful payments count for period
        $successfulPayments = ( clone $baseQuery )->count();

        // Previous period payments for comparison
        $prevPeriodPayments = 0;
        if ($prevPeriodStartDb && $periodStartDb) {
            $prevPeriodPayments = PaymentInvoice::query()
                ->where('isPaid', true)
                ->where('paidAt', '>=', $prevPeriodStartDb)
                ->where('paidAt', '<', $periodStartDb)
                ->count();
        }

        // Promo usage for period
        $promoUsageQuery = ( clone $baseQuery )->where('promoCode_id', 'is not', null);
        $promoUsage = $promoUsageQuery->count();

        // Previous period promo usage
        $prevPromoUsage = 0;
        if ($prevPeriodStartDb && $periodStartDb) {
            $prevPromoUsage = PaymentInvoice::query()
                ->where('isPaid', true)
                ->where('paidAt', '>=', $prevPeriodStartDb)
                ->where('paidAt', '<', $periodStartDb)
                ->where('promoCode_id', 'is not', null)
                ->count();
        }

        // Total revenue for period
        $totalRevenueQuery = ( clone $baseQuery )->buildQuery();
        $totalRevenueQuery->columns([new \Cycle\Database\Injection\Fragment('COALESCE(SUM(amount),0) as sum')]);
        $totalRevenue = (float) ( $totalRevenueQuery->limit(1)->fetchAll()[0]['sum'] ?? 0 );

        // Previous period revenue
        $prevPeriodRevenue = 0;
        if ($prevPeriodStartDb && $periodStartDb) {
            $prevRevenueQuery = PaymentInvoice::query()
                ->where('isPaid', true)
                ->where('paidAt', '>=', $prevPeriodStartDb)
                ->where('paidAt', '<', $periodStartDb)
                ->buildQuery();
            $prevRevenueQuery->columns([new \Cycle\Database\Injection\Fragment('COALESCE(SUM(amount),0) as sum')]);
            $prevPeriodRevenue = (float) ( $prevRevenueQuery->limit(1)->fetchAll()[0]['sum'] ?? 0 );
        }

        // Average payment amount
        $avgPayment = $successfulPayments > 0 ? $totalRevenue / $successfulPayments : 0;

        // Calculate percentage differences
        $revenueDiff = $prevPeriodRevenue > 0
            ? ( ( $totalRevenue - $prevPeriodRevenue ) / $prevPeriodRevenue ) * 100
            : ( $totalRevenue > 0 ? 100 : 0 );

        $paymentsDiff = $prevPeriodPayments > 0
            ? ( ( $successfulPayments - $prevPeriodPayments ) / $prevPeriodPayments ) * 100
            : ( $successfulPayments > 0 ? 100 : 0 );

        $promoUsageDiff = $prevPromoUsage > 0
            ? ( ( $promoUsage - $prevPromoUsage ) / $prevPromoUsage ) * 100
            : ( $promoUsage > 0 ? 100 : 0 );

        return [
            'period_revenue' => [
                'value' => number_format($totalRevenue, 2) . ' ' . config('lk.currency_view'),
                'diff' => round($revenueDiff, 1),
            ],
            'avg_payment' => [
                'value' => number_format($avgPayment, 2) . ' ' . config('lk.currency_view'),
                'diff' => 0,
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
     */
    protected function calculatePaymentChartData(): array
    {
        $cacheKey = 'admin_dashboard_payment_chart_' . $this->paymentPeriod;

        return cache()->callback($cacheKey, fn() => $this->doCalculatePaymentChartData(), 120);
    }

    protected function doCalculatePaymentChartData(): array
    {
        $appTz = new DateTimeZone(config('app.timezone', 'UTC'));
        $dbTz = new DateTimeZone('UTC');

        $now = new DateTimeImmutable('now', $appTz);
        $periodDays = $this->getPeriodDays() ?? 365; // Default to 1 year for "all time"

        // Determine grouping strategy based on period
        if ($periodDays <= 14) {
            // Daily grouping for up to 2 weeks
            $groupBy = 'day';
            $pointsCount = $periodDays;
            $dateFormat = 'D';
        } elseif ($periodDays <= 90) {
            // Weekly grouping for up to 3 months
            $groupBy = 'week';
            $pointsCount = (int) ceil($periodDays / 7);
            $dateFormat = 'd M';
        } else {
            // Monthly grouping for longer periods
            $groupBy = 'month';
            $pointsCount = (int) ceil($periodDays / 30);
            $dateFormat = 'M Y';
        }

        $revenue = [];
        $payments = [];
        $labels = [];

        if ($groupBy === 'day') {
            $startDate = $now->modify('-' . ( $periodDays - 1 ) . ' days')->setTime(0, 0);

            for ($i = 0; $i < $pointsCount; $i++) {
                $dayStart = $startDate->modify("+{$i} day");
                $dayEnd = $dayStart->modify('+1 day');
                $labels[] = Carbon::parse($dayStart)->translatedFormat($dateFormat);

                $dayStartDb = $dayStart->setTimezone($dbTz);
                $dayEndDb = $dayEnd->setTimezone($dbTz);

                $revenueQuery = PaymentInvoice::query()
                    ->where('isPaid', true)
                    ->where('paidAt', '>=', $dayStartDb)
                    ->where('paidAt', '<', $dayEndDb)
                    ->buildQuery();
                $revenueQuery->columns([new \Cycle\Database\Injection\Fragment('COALESCE(SUM(amount),0) as sum')]);
                $sum = $revenueQuery->limit(1)->fetchAll()[0]['sum'] ?? 0;

                $cnt = PaymentInvoice::query()
                    ->where('isPaid', true)
                    ->where('paidAt', '>=', $dayStartDb)
                    ->where('paidAt', '<', $dayEndDb)
                    ->count();

                $revenue[] = (float) $sum;
                $payments[] = (int) $cnt;
            }
        } elseif ($groupBy === 'week') {
            $startDate = $now->modify('-' . ( ( $pointsCount * 7 ) - 1 ) . ' days')->setTime(0, 0);

            for ($i = 0; $i < $pointsCount; $i++) {
                $weekStart = $startDate->modify('+' . ( $i * 7 ) . ' days');
                $weekEnd = $weekStart->modify('+7 days');
                $labels[] = Carbon::parse($weekStart)->translatedFormat($dateFormat);

                $weekStartDb = $weekStart->setTimezone($dbTz);
                $weekEndDb = $weekEnd->setTimezone($dbTz);

                $revenueQuery = PaymentInvoice::query()
                    ->where('isPaid', true)
                    ->where('paidAt', '>=', $weekStartDb)
                    ->where('paidAt', '<', $weekEndDb)
                    ->buildQuery();
                $revenueQuery->columns([new \Cycle\Database\Injection\Fragment('COALESCE(SUM(amount),0) as sum')]);
                $sum = $revenueQuery->limit(1)->fetchAll()[0]['sum'] ?? 0;

                $cnt = PaymentInvoice::query()
                    ->where('isPaid', true)
                    ->where('paidAt', '>=', $weekStartDb)
                    ->where('paidAt', '<', $weekEndDb)
                    ->count();

                $revenue[] = (float) $sum;
                $payments[] = (int) $cnt;
            }
        } else {
            // Monthly grouping
            $startDate = $now->modify('-' . $pointsCount . ' months');

            for ($i = 0; $i < $pointsCount; $i++) {
                $monthStart = $startDate->modify('+' . $i . ' month');
                $monthEnd = $monthStart->modify('+1 month');
                $labels[] = Carbon::parse($monthStart)->translatedFormat($dateFormat);

                $monthStartDb = $monthStart->setTimezone($dbTz);
                $monthEndDb = $monthEnd->setTimezone($dbTz);

                $revenueQuery = PaymentInvoice::query()
                    ->where('isPaid', true)
                    ->where('paidAt', '>=', $monthStartDb)
                    ->where('paidAt', '<', $monthEndDb)
                    ->buildQuery();
                $revenueQuery->columns([new \Cycle\Database\Injection\Fragment('COALESCE(SUM(amount),0) as sum')]);
                $sum = $revenueQuery->limit(1)->fetchAll()[0]['sum'] ?? 0;

                $cnt = PaymentInvoice::query()
                    ->where('isPaid', true)
                    ->where('paidAt', '>=', $monthStartDb)
                    ->where('paidAt', '<', $monthEndDb)
                    ->count();

                $revenue[] = (float) $sum;
                $payments[] = (int) $cnt;
            }
        }

        return [
            'series' => [
                [
                    'name' => __('admin-dashboard.charts.daily_revenue'),
                    'data' => $revenue,
                ],
                [
                    'name' => __('admin-dashboard.charts.daily_payments'),
                    'data' => $payments,
                ],
            ],
            'labels' => $labels,
        ];
    }

    /**
     * Calculate payment methods distribution
     */
    protected function calculatePaymentMethodsData(): array
    {
        $cacheKey = 'admin_dashboard_payment_methods_' . $this->paymentPeriod;

        return cache()->callback($cacheKey, fn() => $this->doCalculatePaymentMethodsData(), 300);
    }

    protected function doCalculatePaymentMethodsData(): array
    {
        $appTz = new DateTimeZone(config('app.timezone', 'UTC'));
        $dbTz = new DateTimeZone('UTC');

        $now = new DateTimeImmutable('now', $appTz);
        $periodDays = $this->getPeriodDays();

        $baseQuery = PaymentInvoice::query()->where('isPaid', true);

        if ($periodDays !== null) {
            $periodStart = $now->modify("-{$periodDays} days")->setTime(0, 0);
            $periodStartDb = $periodStart->setTimezone($dbTz);
            $baseQuery->where('paidAt', '>=', $periodStartDb);
        }

        $gatewayQuery = $baseQuery->buildQuery();
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
     */
    protected function getMainTab(): array
    {
        $userMetrics = $this->calculateUserMetrics();
        $userRegistrationData = $this->calculateUserRegistrationData();

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

        $filters = Filters::make()->period('user_period')->compact();

        $periodDescription = $this->getUserPeriodDescription();

        return [
            'tab' => Tab::make(__('admin-dashboard.tabs.main'))
                ->icon('ph.regular.users')
                ->layouts([
                    $metrics,
                    $filters,
                    Chart::make('user_registrations', __('admin-dashboard.charts.user_registrations'))
                        ->type('area')
                        ->height(340)
                        ->colors(['#3b82f6', '#60a5fa'])
                        ->description($periodDescription)
                        ->dataset($userRegistrationData['series'])
                        ->labels($userRegistrationData['labels']),
                ]),
            'vars' => $userMetrics,
        ];
    }

    /**
     * Get user period description for chart
     */
    protected function getUserPeriodDescription(): string
    {
        $periodDays = $this->getUserPeriodDays();

        if ($periodDays === null) {
            return __('admin-dashboard.descriptions.user_registrations_all');
        }

        return __('admin-dashboard.descriptions.user_registrations_period', ['days' => $periodDays]);
    }

    /**
     * Get the payments tab
     */
    protected function getPaymentsTab(): array
    {
        $paymentMetrics = $this->calculatePaymentMetrics();
        $paymentChartData = $this->calculatePaymentChartData();
        $paymentMethodsData = $this->calculatePaymentMethodsData();

        $metrics = LayoutFactory::metrics([
            'admin-dashboard.metrics.period_revenue' => 'vars.period_revenue',
            'admin-dashboard.metrics.avg_payment' => 'vars.avg_payment',
            'admin-dashboard.metrics.successful_payments' => 'vars.successful_payments',
            'admin-dashboard.metrics.promo_usage' => 'vars.promo_usage',
        ])->setIcons([
            'admin-dashboard.metrics.period_revenue' => 'money',
            'admin-dashboard.metrics.avg_payment' => 'currency-circle-dollar',
            'admin-dashboard.metrics.successful_payments' => 'check-circle',
            'admin-dashboard.metrics.promo_usage' => 'ticket',
        ]);

        $periodDescription = $this->getPeriodDescription();

        $filters = Filters::make()->period('payment_period')->compact();

        return [
            'tab' => Tab::make(__('admin-dashboard.tabs.payments'))
                ->icon('ph.regular.currency-circle-dollar')
                ->layouts([
                    $filters,
                    $metrics,
                    LayoutFactory::split([
                        Chart::make('payment_stats', __('admin-dashboard.charts.payment_stats'))
                            ->type('area')
                            ->height(340)
                            ->colors(['#10b981', '#6366f1'])
                            ->description($periodDescription)
                            ->dataset($paymentChartData['series'])
                            ->labels($paymentChartData['labels']),
                        Chart::make('payment_methods', __('admin-dashboard.charts.payment_methods'))
                            ->type('donut')
                            ->height(340)
                            ->colors(['#8b5cf6', '#3b82f6', '#10b981', '#f59e0b'])
                            ->description(__('admin-dashboard.descriptions.payment_methods'))
                            ->dataset($paymentMethodsData['series'])
                            ->labels($paymentMethodsData['labels']),
                    ]),
                ]),
            'vars' => $paymentMetrics,
        ];
    }

    /**
     * Get period description for chart
     */
    protected function getPeriodDescription(): string
    {
        $periodDays = $this->getPeriodDays();

        if ($periodDays === null) {
            return __('admin-dashboard.descriptions.payment_stats_all');
        }

        return __('admin-dashboard.descriptions.payment_stats_period', ['days' => $periodDays]);
    }

    /**
     * Register default dashboard tabs
     */
    protected function registerDefaultTabs(): void
    {
        $currentTab = request()->input('tab-dashboard_tabs');
        $mainTabSlug = \Illuminate\Support\Str::slug(__('admin-dashboard.tabs.main'));
        $paymentsTabSlug = \Illuminate\Support\Str::slug(__('admin-dashboard.tabs.payments'));

        $showPayments = user()->can('admin.gateways') || user()->can('admin.boss');

        if ($showPayments && $currentTab === $paymentsTabSlug) {
            $mainTab = $this->getMainTabPlaceholder();
            $paymentsTab = $this->getPaymentsTab();
        } elseif ($currentTab === null || $currentTab === $mainTabSlug) {
            $mainTab = $this->getMainTab();
            $paymentsTab = $showPayments ? $this->getPaymentsTabPlaceholder() : null;
        } else {
            $mainTab = $this->getMainTabPlaceholder();
            $paymentsTab = $showPayments ? $this->getPaymentsTabPlaceholder() : null;
        }

        $this->addTab($mainTab['tab'], $mainTab['vars']);

        if ($paymentsTab !== null) {
            $this->addTab($paymentsTab['tab'], $paymentsTab['vars']);
        }
    }

    /**
     * Get main tab placeholder (no data loaded)
     */
    protected function getMainTabPlaceholder(): array
    {
        return [
            'tab' => Tab::make(__('admin-dashboard.tabs.main'))->icon('ph.regular.users')->layouts([]),
            'vars' => [],
        ];
    }

    /**
     * Get payments tab placeholder (no data loaded)
     */
    protected function getPaymentsTabPlaceholder(): array
    {
        return [
            'tab' => Tab::make(__('admin-dashboard.tabs.payments'))
                ->icon('ph.regular.currency-circle-dollar')
                ->layouts([]),
            'vars' => [],
        ];
    }
}
