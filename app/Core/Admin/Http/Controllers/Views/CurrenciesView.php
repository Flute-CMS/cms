<?php

namespace Flute\Core\Admin\Http\Controllers\Views;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Database\Entities\Currency;
use Flute\Core\Database\Entities\PaymentGateway;
use Flute\Core\Support\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class CurrenciesView extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.currency');
    }

    public function list(): Response
    {
        $table = table();

        $table->setPhrases([
            'code' => __('admin.currency.currency'),
            'minimum_value' => __('admin.currency.min_value'),
            'exchange_rate' => __('admin.currency.exchange_rate'),
        ]);

        $result = rep(Currency::class)->select();

        $result = $result->fetchAll();

        $table->fromEntity($result, ['paymentGateways'])->withActions('currency');

        return view("Core/Admin/Http/Views/pages/currency/list", [
            'table' => $table->render()
        ]);
    }

    public function update($id): Response
    {
        $currency = rep(Currency::class)->findByPK($id);

        if (!$currency)
            return $this->error(__("admin.currency.not_found"), 404);

        return view('Core/Admin/Http/Views/pages/currency/edit', [
            'currency' => $currency,
            'payments' => $this->getGateways()
        ]);
    }

    public function add(): Response
    {
        return view('Core/Admin/Http/Views/pages/currency/add', [
            'payments' => $this->getGateways()
        ]);
    }

    protected function getGateways(): array
    {
        return rep(PaymentGateway::class)->select()->where('enabled', true)->fetchAll();
    }
}