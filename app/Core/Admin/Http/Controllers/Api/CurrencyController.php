<?php

namespace Flute\Core\Admin\Http\Controllers\Api;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Database\Entities\Currency;
use Flute\Core\Database\Entities\PaymentGateway;
use Flute\Core\Http\Middlewares\CSRFMiddleware;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;

class CurrencyController extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.currency');
        $this->middleware(HasPermissionMiddleware::class);
        $this->middleware(CSRFMiddleware::class);
    }

    public function add(FluteRequest $request)
    {
        $currency = rep(Currency::class)->findOne([
            'code' => $request->currency,
        ]);

        if ($currency)
            return $this->error(__('admin.currency.exists', ['name' => $request->currency]), 403);

        $currency = new Currency;
        $currency->code = $request->currency;
        $currency->minimum_value = $request->minimum_value;
        $currency->exchange_rate = $request->exchange_rate;

        $this->assignPayments($request->gateways, $currency);

        user()->log('events.currency_added', $request->currency);

        transaction($currency)->run();

        return $this->success();
    }

    public function delete(FluteRequest $request, string $id)
    {
        $currency = $this->getCurrency((int) $id);

        if (!$currency)
            return $this->error(__('admin.currency.not_found'), 404);

        user()->log('events.currency_deleted', $currency->code);

        transaction($currency, 'delete')->run();

        return $this->success();
    }

    public function edit(FluteRequest $request, string $id)
    {
        $currency = rep(Currency::class)->findOne([
            'id' => $id,
        ]);

        if (!$currency)
            return $this->error(__('admin.currency.exists', ['name' => $request->currency]), 403);

        $currency->code = $request->currency;
        $currency->minimum_value = $request->minimum_value;
        $currency->exchange_rate = $request->exchange_rate;

        $currency->clearPayments();

        $this->assignPayments($request->gateways, $currency);

        user()->log('events.currency_edited', $id);

        transaction($currency)->run();

        return $this->success();
    }

    protected function assignPayments(array $payments, Currency $currency)
    {
        foreach ($payments as $key => $gateway) {
            if (!filter_var($gateway, FILTER_VALIDATE_BOOLEAN))
                continue;

            $paymentGateway = rep(PaymentGateway::class)->findByPK((int) $key);

            if ($paymentGateway && $paymentGateway->enabled == true) {
                $currency->addPayment($paymentGateway);
            }
        }
    }

    protected function getCurrency(int $id)
    {
        return rep(Currency::class)->findByPK($id);
    }
}