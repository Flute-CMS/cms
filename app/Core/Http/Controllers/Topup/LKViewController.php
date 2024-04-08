<?php

namespace Flute\Core\Http\Controllers\Topup;

use Flute\Core\Database\Entities\Currency;
use Flute\Core\Support\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class LKViewController extends AbstractController
{
    public function index(): Response
    {
        $currencies = rep(Currency::class)->findAll();

        $currencyExchangeRates = [];
        foreach ($currencies as $currency) {
            $currencyExchangeRates[$currency->code] = $currency->exchange_rate;
        }

        $currencyGateways = [];
        foreach ($currencies as $currency) {
            foreach ($currency->paymentGateways as $gateway) {
                $currencyGateways[$currency->code][] = $gateway->name;
            }
        }

        $currencyMinimumAmounts = [];
        foreach ($currencies as $currency) {
            $currencyMinimumAmounts[$currency->code] = $currency->minimum_value;
        }

        return view(tt('pages/lk/index'), [
            'payments' => payments()->getAllGateways(),
            'currencies' => $currencies,
            'currencyExchangeRates' => $currencyExchangeRates,
            'currencyGateways' => $currencyGateways,
            'currencyMinimumAmounts' => $currencyMinimumAmounts
        ]);
    }

    public function paymentFail(): Response
    {
        return view(tt('pages/lk/fail'));
    }

    public function paymentSuccess(): Response
    {
        return view(tt('pages/lk/success'));
    }
}