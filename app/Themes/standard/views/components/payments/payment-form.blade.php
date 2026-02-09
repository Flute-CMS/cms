@php
    /**
     * @var string $currency
     * @var array $currencies
     * @var array $currencyGateways
     * @var array $currencyExchangeRates
     * @var array $currencyMinimumAmounts
     * @var bool $agree
     */

    $hasCurrencies = count($currencies) > 0;
    $hasGateways = !empty($currencyGateways[$currency]);
    $singleCurrency = count($currencies) === 1;
    $singleGateway = $hasGateways && count($currencyGateways[$currency]) === 1;
    $isConfigured = $hasCurrencies && $hasGateways;

    $defaultGateway = null;
    if ($hasGateways) {
        $keys = array_keys($currencyGateways[$currency]);
        $defaultGateway = $singleGateway ? $keys[0] : ($gateway ?? $keys[0]);
    }

    // JSON config for JS
    $jsConfig = [
        'currencies' => $currencies,
        'gateways' => $currencyGateways,
        'exchangeRates' => $currencyExchangeRates,
        'minimumAmounts' => $currencyMinimumAmounts,
        'currencyView' => config('lk.currency_view'),
        'ofertaView' => (bool) config('lk.oferta_view'),
        'maxAmount' => config('lk.max_single_amount', 1000000),
        'presets' => [
            'RUB' => [500, 1000, 2500, 5000],
            'USD' => [5, 10, 25, 50],
            'EUR' => [5, 10, 25, 50],
            'UAH' => [200, 500, 1000, 2500],
            'KZT' => [2000, 5000, 10000, 25000],
            '_default' => [500, 1000, 2500, 5000],
        ],
        'i18n' => [
            'min_amount_info' => __('lk.min_amount_info', ['amount' => ':amount', 'currency' => ':currency']),
            'base_amount' => __('lk.base_amount'),
            'gateway_fee' => __('lk.gateway_fee'),
            'gateway_fee_tooltip' => __('lk.gateway_fee_tooltip'),
            'gateway_bonus' => __('lk.gateway_bonus'),
            'you_will_receive' => __('lk.you_will_receive'),
            'bonus' => __('lk.bonus'),
            'discount' => __('lk.discount'),
            'top_up_button' => __('lk.top_up_button'),
            'preset_amounts' => __('lk.preset_amounts'),
            'select_currency' => __('lk.select_currency'),
            'select_gateway' => __('lk.select_gateway'),
        ],
    ];
@endphp

<div class="lk-page" id="lk-app" data-config='@json($jsConfig)'>
    @if (!$isConfigured)
        <div class="lk-empty">
            <div class="lk-empty__icon"><x-icon path="ph.regular.wallet" /></div>
            <h3 class="lk-empty__title">{{ __('lk.payments_not_configured') }}</h3>
            <p class="lk-empty__text">{{ __('lk.payments_not_configured_text') }}</p>
        </div>
    @else
        {{-- Header --}}
        @if (!$isModal && !config('lk.only_modal'))
            <header class="lk-header">
                <h1 class="lk-header__title">{{ __('lk.title') }}</h1>
                <p class="lk-header__sub">{{ __('lk.payment_form_subtitle') }}</p>
            </header>
        @endif

        {{-- Main card --}}
        <form class="lk-card" id="lk-form" aria-label="{{ __('lk.payment_form') }}">
            {{-- 1. Currency + Gateway --}}
            <div class="lk-card__section">
                @include('flute::components.payments.partials.currency-picker', [
                    'currencies' => $currencies,
                    'currency' => $currency,
                    'singleCurrency' => $singleCurrency,
                ])
                @include('flute::components.payments.partials.gateway-grid', [
                    'currencyGateways' => $currencyGateways,
                    'currency' => $currency,
                    'gateway' => $defaultGateway,
                    'singleGateway' => $singleGateway,
                    'hasGateways' => $hasGateways,
                ])
            </div>

            {{-- 2. Amount (shown by JS when gateway selected) --}}
            <div class="lk-card__divider" data-lk-section="amount-divider" style="display:none"></div>
            <div class="lk-card__section" data-lk-section="amount" style="display:none">
                @include('flute::components.payments.partials.amount-input', [
                    'currency' => $currency,
                    'effectiveMinAmount' => $currencyMinimumAmounts[$currency] ?? 0,
                ])
            </div>

            {{-- 3. Checkout (shown by JS when amount entered) --}}
            <div class="lk-card__divider" data-lk-section="checkout-divider" style="display:none"></div>
            <div class="lk-card__section" data-lk-section="checkout" style="display:none">
                @include('flute::components.payments.partials.checkout-summary')
            </div>
        </form>

        <p class="lk-footnote">{{ __('lk.gateway_disclaimer') }}</p>

        <script src="@asset('assets/js/lk-payment.js')"></script>
    @endif
</div>
