<?php

namespace Flute\Admin\Packages\Currency\Services;

use Flute\Core\Database\Entities\Currency;

class CurrencyRateService
{
    protected const API_URL = 'https://open.er-api.com/v6/latest/';

    public function updateAutoRates(): int
    {
        $autoCurrencies = Currency::query()->where('auto_rate', true)->fetchAll();

        if (empty($autoCurrencies)) {
            return 0;
        }

        $baseCurrency = Currency::query()
            ->where('auto_rate', false)
            ->orderBy('exchange_rate', 'asc')
            ->fetchOne();

        $baseCode = $baseCurrency ? $baseCurrency->code : 'USD';

        $rates = $this->fetchRates($baseCode);
        if ($rates === null) {
            logs('cron')->error('Currency rate fetch failed for base: ' . $baseCode);

            return 0;
        }

        $updated = 0;
        foreach ($autoCurrencies as $currency) {
            $code = strtoupper($currency->code);
            if (!isset($rates[$code])) {
                continue;
            }

            $rate = (float) $rates[$code];
            if ($currency->rate_markup > 0) {
                $rate *= 1 + ( $currency->rate_markup / 100 );
            }

            $currency->exchange_rate = round($rate, 4);
            $currency->save();
            $updated++;
        }

        if ($updated > 0) {
            cache()->delete('flute.currencies');
        }

        return $updated;
    }

    public function fetchRates(string $baseCurrency): ?array
    {
        $url = self::API_URL . urlencode($baseCurrency);

        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'method' => 'GET',
                'header' => 'Accept: application/json',
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);
        if (!is_array($data) || ( $data['result'] ?? '' ) !== 'success' || !isset($data['rates'])) {
            return null;
        }

        return $data['rates'];
    }
}
