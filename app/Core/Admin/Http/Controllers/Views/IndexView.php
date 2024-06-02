<?php

namespace Flute\Core\Admin\Http\Controllers\Views;

use Flute\Core\Admin\Builders\AdminMainBuilder;
use Flute\Core\Admin\Builders\AdminSidebarBuilder;
use Flute\Core\Charts\FluteChart;
use Flute\Core\Database\Entities\PaymentGateway;
use Flute\Core\Database\Entities\PaymentInvoice;
use Flute\Core\Database\Entities\User;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\Response;

class IndexView extends AbstractController
{
    public function __construct()
    {
        // HasPermissionMiddleware::permission('admin.stats');
    }

    public function index(FluteRequest $request)
    {
        return view("Core/Admin/Http/Views/pages/index");
    }

    public function dashboard(FluteRequest $request)
    {
        $this->buildWeekArea();
        $this->buildRegistrationsArea();

        $this->buildLineChart();
        $this->buildPie();

        return view("Core/Admin/Http/Views/pages/dashboard");
    }

    protected function buildLineChart()
    {
        $endDate = (new \DateTime())->modify('+1 day'); // костыль, мне пох
        $startDate = (new \DateTime())->modify('-1 month');

        $paymentSums = db()->query('
            SELECT
                DATE(paid_at) AS date, 
                SUM(amount) AS total
            FROM
                flute_payment_invoices
            WHERE
                paid_at >= ?
                AND paid_at <= ?
                AND is_paid = 1
            GROUP BY
                DATE(paid_at)
            ORDER BY
                date;',
            [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            ]
        )->fetchAll();

        // Подготовка данных для графика
        $dates = [];
        $totals = [];
        foreach ($paymentSums as $sum) {
            $dates[] = $sum['date'];
            $totals[] = $sum['total'];
        }

        // Создание линейного графика
        $chart = (new FluteChart)->lineChart()
            ->addData('', $totals)
            ->setTitle(__('admin.dashboard_page.payments_month'))
            ->setXAxis($dates);

        AdminMainBuilder::add($chart, 6);
    }

    protected function buildWeekArea()
    {
        $startDate = new \DateTime('monday this week');
        $endDate = new \DateTime('sunday this week');

        // Запрос к базе данных
        $payments = rep(PaymentInvoice::class)->select()
            ->where('paid_at', '>=', $startDate)
            ->where('paid_at', '<=', $endDate)
            ->where('is_paid', true)
            ->fetchAll();

        // Подготовка данных для графика
        $paymentData = []; // массив для сумм платежей по дням
        foreach ($payments as $payment) {
            $day = $payment->paidAt->format('d M');
            if (!isset($paymentData[$day])) {
                $paymentData[$day] = 0;
            }
            $paymentData[$day] += $payment->amount;
        }

        // Создание графика
        $chart = (new FluteChart)->areaChart()
            ->addData('', array_values($paymentData))
            ->setSparkline(true)
            ->setHeight(250)
            ->setTitle(array_sum($paymentData) . ' ' . config('lk.currency_view'))
            ->setSubtitle(__('admin.dashboard_page.payments_week'))
            ->setXAxis(array_keys($paymentData));

        AdminMainBuilder::add($chart, 6);
    }
    protected function buildRegistrationsArea()
    {
        $startDate = new \DateTime('monday this week');
        $endDate = (new \DateTime('sunday this week'))->setTime(23, 59, 59);

        // Запрос к базе данных
        $userRegistrations = rep(User::class)->select()
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->orderBy('created_at')
            ->fetchAll();

        // Подготовка данных для графика
        $registrationData = []; // массив для количества регистраций по дням
        foreach ($userRegistrations as $user) {
            $dayOfWeek = $user->created_at->format('l');
            if (!isset($registrationData[$dayOfWeek])) {
                $registrationData[$dayOfWeek] = 0;
            }
            $registrationData[$dayOfWeek]++;
        }

        // Создание столбчатой диаграммы
        $chart = (new FluteChart)->areaChart()
            ->addData('Кол-во', array_values($registrationData))
            ->setSparkline(true)
            ->setHeight(250)
            ->setTitle(__('admin.dashboard_page.registrations'))
            ->setSubtitle(__('admin.dashboard_page.registrations_desc'))
            ->setXAxis(array_keys($registrationData));

        AdminMainBuilder::add($chart, 6);
    }

    protected function buildPie()
    {
        // Получение сумм платежей, сгруппированных по шлюзу
        $payments = rep(PaymentInvoice::class)->select()
            ->where('is_paid', true)
            ->fetchAll();

        // Подготовка данных для круговой диаграммы
        $paymentData = [];
        foreach ($payments as $payment) {
            $gatewayKey = $payment->gateway;
            if (!isset($paymentData[$gatewayKey])) {
                $paymentData[$gatewayKey] = 0;
            }
            $paymentData[$gatewayKey] += $payment->amount;
        }

        // Подготовка меток для круговой диаграммы
        $gatewayLabels = [];
        $gateways = rep(PaymentGateway::class)->findAll();
        foreach ($gateways as $gateway) {
            if (array_key_exists($gateway->adapter, $paymentData)) {
                $gatewayLabels[$gateway->adapter] = $gateway->name;
            }
        }

        // Создание круговой диаграммы
        $chart = (new FluteChart)->pieChart()
            ->addData(array_values($paymentData))
            ->setTitle(__('admin.dashboard_page.payments_by_gateways'))
            ->setLabels(array_values($gatewayLabels));

        AdminMainBuilder::add($chart, 6);
    }
}