@php
    /**
     * @var string $currency
     * @var array $currencies
     * @var array $currencyGateways
     * @var array $currencyExchangeRates
     * @var array $currencyMinimumAmounts
     * @var string|null $gateway
     * @var float|null $amount
     * @var string|null $promoCode
     * @var bool $promoIsValid
     * @var array|null $promoDetails
     * @var float $amountToPay
     * @var float $amountToReceive
     * @var float $gatewayFee
     * @var float $gatewayFeeAmount
     * @var float $gatewayBonus
     * @var float $gatewayBonusAmount
     * @var bool $agree
     */

    $hasCurrencies = count($currencies) > 0;
    $hasGateways = !empty($currencyGateways[$currency]);
    $singleCurrency = count($currencies) === 1;
    $singleGateway = $hasGateways && count($currencyGateways[$currency]) === 1;
    $isConfigured = $hasCurrencies && $hasGateways;

    $presetAmounts = match ($currency) {
        'RUB' => [500, 1000, 2500, 5000],
        'USD' => [5, 10, 25, 50],
        'EUR' => [5, 10, 25, 50],
        'UAH' => [200, 500, 1000, 2500],
        'KZT' => [2000, 5000, 10000, 25000],
        default => [500, 1000, 2500, 5000],
    };

    $currentGatewayData = $currencyGateways[$currency][$gateway] ?? null;
@endphp

<div class="lk-payment-container">
    @if (!$isConfigured)
        {{-- Empty state: No currencies or gateways configured --}}
        <div class="lk-payment-empty">
            <div class="lk-payment-empty-icon">
                <x-icon path="ph.regular.wallet" />
            </div>
            <h3 class="lk-payment-empty-title">{{ __('lk.payments_not_configured') }}</h3>
            <p class="lk-payment-empty-text">{{ __('lk.payments_not_configured_text') }}</p>
        </div>
    @else
        <div class="lk-payment-layout">
            {{-- LEFT COLUMN: Selection --}}
            <div class="lk-payment-main">
                <form class="lk-form" id="payment-form" yoyo:post="purchase" yoyo:on="submit" aria-live="polite"
                    role="form" aria-label="{{ __('lk.payment_form') }}">

                    @if (!$isModal && !config('lk.only_modal'))
                        {{-- Header --}}
                        <x-legend title="{{ __('lk.title') }}" description="{{ __('lk.payment_form_subtitle') }}" />
                    @endif

                    {{-- Currency Section --}}
                    <div class="lk-payment-section" id="currency-section">
                        <div class="lk-currency-tabs {{ $singleCurrency ? 'single-currency' : '' }}" role="radiogroup"
                            aria-label="{{ __('lk.select_currency') }}">
                            @foreach ($currencies as $code)
                                <div class="lk-currency-tabs-item">
                                    <input type="radio" id="currency__{{ $code }}" name="currency"
                                        value="{{ $code }}" @checked($currency === $code) yoyo
                                        aria-label="{{ __('lk.currency_option', ['code' => $code]) }}" data-noprogress
                                        {{ $singleCurrency ? 'disabled' : '' }} />
                                    <label for="currency__{{ $code }}" tabindex="0">
                                        {{ $code }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Gateway Section --}}
                    @if ($hasGateways)
                        <div class="lk-payment-section" id="gateway-section">
                            <ul class="lk-gateways {{ $singleGateway ? 'single-gateway' : '' }}" role="radiogroup"
                                aria-label="{{ __('lk.select_gateway') }}">
                                @foreach ($currencyGateways[$currency] as $key => $gatewayObject)
                                    @php
                                        $gatewayName = $gatewayObject['name'];
                                        $gatewayImage = $gatewayObject['image'] ?? null;
                                        $gatewayDescription = $gatewayObject['description'] ?? '';
                                        $gatewayFeeVal = $gatewayObject['fee'] ?? 0;
                                        $gatewayBonusVal = $gatewayObject['bonus'] ?? 0;
                                        $isSelected = $gateway === $key || ($singleGateway && $loop->first);
                                    @endphp
                                    <li class="lk-gateways-item">
                                        <input type="radio" id="gateway__{{ $key }}" name="gateway"
                                            value="{{ $key }}" @checked($isSelected) yoyo
                                            aria-label="{{ __('lk.gateway_option', ['name' => $gatewayName]) }}"
                                            data-noprogress {{ $singleGateway ? 'disabled' : '' }} />
                                        <label for="gateway__{{ $key }}" tabindex="0">
                                            <div class="lk-gateway-left">
                                                @if ($gatewayImage)
                                                    <div class="lk-gateway-image">
                                                        <img src="{{ asset($gatewayImage) }}"
                                                            alt="{{ $gatewayName }}" loading="lazy" />
                                                    </div>
                                                @else
                                                    <div class="lk-gateway-icon">
                                                        <x-icon path="ph.regular.credit-card" />
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="lk-gateway-center">
                                                <div class="lk-gateway-title-row">
                                                    <span class="lk-gateway-name">{{ $gatewayName }}</span>
                                                    @if ($gatewayBonusVal > 0)
                                                        <span
                                                            class="lk-gateway-bonus-badge">+{{ $gatewayBonusVal }}%</span>
                                                    @endif
                                                </div>
                                                @if ($gatewayDescription)
                                                    <span
                                                        class="lk-gateway-description">{{ $gatewayDescription }}</span>
                                                @endif
                                                @if ($gatewayFeeVal > 0)
                                                    <span class="lk-gateway-fee">{{ $gatewayFeeVal }}%
                                                        {{ __('lk.fee') }}</span>
                                                @endif
                                            </div>
                                            <div class="lk-gateway-right">
                                                <div class="lk-gateway-check">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="4" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="20 6 9 17 4 12"></polyline>
                                                    </svg>
                                                </div>
                                            </div>
                                        </label>
                                    </li>
                                @endforeach
                            </ul>
                            @if ($singleGateway)
                                <input type="hidden" name="gateway"
                                    value="{{ array_key_first($currencyGateways[$currency]) }}" />
                            @endif
                        </div>
                    @else
                        <div class="lk-payment-section">
                            <div class="lk-payment-error">
                                {{ __('lk.no_gateways_for_currency') }}
                            </div>
                        </div>
                    @endif

                    <small class="lk-payment-hint mt-2">
                        {{ __('lk.gateway_disclaimer') }}
                    </small>
                </form>
            </div>

            {{-- RIGHT COLUMN: Summary Terminal --}}
            <div class="lk-payment-summary" id="payment-summary">
                @if ($currency && $hasGateways && ($gateway || $singleGateway))
                    {{-- Amount Input --}}
                    <div class="lk-amount-section">
                        <div class="lk-amount-label">
                            <span>{{ __('lk.top_up_amount') }}</span>
                            <x-badge type="success" class="lk-amount-secure">
                                <x-icon path="ph.regular.lock-simple" />
                                {{ __('lk.secure') }}
                            </x-badge>
                        </div>

                        <x-forms.field yoyo yoyo:on="input changed delay:500ms" data-noprogress class="lk-field">
                            <div class="lk-amount-input-wrapper">
                                <input type="text" name="amount" id="amount" form="payment-form"
                                    inputmode="decimal" autocomplete="off"
                                    min="{{ $currencyMinimumAmounts[$currency] }}" step="0.01"
                                    value="{{ $amount }}" required placeholder="0"
                                    aria-describedby="amount-description" />
                                <span class="lk-amount-currency">{{ $currency }}</span>
                            </div>
                        </x-forms.field>

                        <small class="lk-payment-hint" id="amount-description">
                            {{ __('lk.min_amount_info', ['amount' => $currencyMinimumAmounts[$currency], 'currency' => $currency]) }}
                        </small>

                        {{-- Preset Amounts --}}
                        <div class="lk-presets" role="group" aria-label="{{ __('lk.preset_amounts') }}">
                            @foreach ($presetAmounts as $preset)
                                <button type="button" class="lk-presets-item {{ $amount == $preset ? 'active' : '' }}"
                                    data-amount="{{ $preset }}" yoyo:on="click"
                                    yoyo:get="setPresetAmount({{ $preset }})" data-noprogress
                                    aria-pressed="{{ $amount == $preset ? 'true' : 'false' }}">
                                    {{ number_format($preset, 0, '', ' ') }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {!! $additionalFields ?? '' !!}

                    {{-- Promo Code (inline compact) --}}
                    @if ($amount)
                        <x-forms.field yoyo yoyo:on="input delay:800ms changed blur" yoyo:post="validatePromo"
                            data-noprogress class="lk-field lk-promo-field">
                            <div
                                class="lk-promo-inline {{ $promoCode && $promoIsValid ? 'is-valid' : '' }} {{ $promoCode && !$promoIsValid ? 'is-invalid' : '' }}">
                                <x-icon path="ph.regular.ticket" class="lk-promo-inline-icon" />
                                <input type="text" name="promoCode" id="promoCode" form="payment-form"
                                    value="{{ $promoCode }}" class="lk-promo-inline-input"
                                    placeholder="{{ __('lk.enter_promo_code') }}" />
                                @if ($promoCode && $promoIsValid)
                                    <x-icon path="ph.bold.check" class="lk-promo-inline-status success" />
                                @elseif ($promoCode && !$promoIsValid)
                                    <x-icon path="ph.bold.x" class="lk-promo-inline-status error" />
                                @endif
                            </div>
                        </x-forms.field>

                        {{-- Summary Details --}}
                        <ul class="lk-details">
                            <li class="lk-details-item">
                                <span>{{ __('lk.base_amount') }}</span>
                                <span>{{ number_format($amount ?? 0, 2) }} {{ $currency }}</span>
                            </li>

                            {{-- Fee explainer line --}}
                            @if ($gatewayFee > 0)
                                <li class="lk-details-item lk-details-fee">
                                    <span>
                                        {{ __('lk.gateway_fee') }}
                                        <x-icon path="ph.regular.info"
                                            data-tooltip="{{ __('lk.gateway_fee_tooltip') }}" />
                                    </span>
                                    <span>{{ $gatewayFee }}% (~{{ number_format($gatewayFeeAmount, 0) }}
                                        {{ $currency }})</span>
                                </li>
                            @endif

                            {{-- Gateway Bonus --}}
                            @if ($gatewayBonus > 0)
                                <li class="lk-details-item lk-details-bonus">
                                    <span>{{ __('lk.gateway_bonus') }} (+{{ $gatewayBonus }}%)</span>
                                    <span>+{{ number_format($gatewayBonusAmount, 0) }}
                                        {{ config('lk.currency_view') }}</span>
                                </li>
                            @endif

                            {{-- Promo Bonus/Discount --}}
                            @if ($promoIsValid && $promoDetails)
                                <li class="lk-details-item lk-details-bonus">
                                    <span>
                                        @if ($promoDetails['type'] === 'amount')
                                            {{ __('lk.bonus') }}
                                        @else
                                            {{ __('lk.discount') }}
                                        @endif
                                    </span>
                                    <span>
                                        @if ($promoDetails['type'] === 'amount')
                                            +{{ $promoDetails['value'] }} {{ $currency }}
                                        @elseif ($promoDetails['type'] === 'percentage')
                                            -{{ $promoDetails['value'] }}%
                                        @endif
                                    </span>
                                </li>
                            @endif

                            <li class="lk-details-item lk-details-total">
                                <span>{{ __('lk.you_will_receive') }}</span>
                                <span class="lk-receive-amount">{{ number_format($amountToReceive ?? 0, 2) }}
                                    {{ config('lk.currency_view') }}</span>
                            </li>
                        </ul>

                        {{-- Terms --}}
                        @if (config('lk.oferta_view'))
                            <div class="lk-payment-terms-section" id="terms-section">
                                <div class="lk-payment-section-content">
                                    <x-forms.field class="lk-field lk-terms-field">
                                        <x-fields.checkbox name="agree" id="agree" form="payment-form"
                                            checked="{{ $agree }}" data-noprogress yoyo
                                            aria-describedby="terms-link">
                                            <x-slot:label>
                                                {{ __('lk.agree_terms') }}
                                                <x-link type="accent"
                                                    href="{{ url(config('lk.oferta_url', '/agreenment')) }}"
                                                    id="terms-link" target="_blank" rel="noopener">
                                                    {{ __('lk.terms_of_offer') }}
                                                </x-link>
                                            </x-slot:label>
                                        </x-fields.checkbox>
                                    </x-forms.field>
                                </div>
                            </div>
                        @endif

                        {{-- Submit Button --}}
                        <x-button size="large" class="lk-payment-submit" withLoading submit form="payment-form"
                            :disabled="($promoCode && !$promoIsValid) || (config('lk.oferta_view') && $agree === false)" aria-label="{{ __('lk.top_up_button') }}">
                            <span>{{ __('lk.top_up_button') }}</span>
                            <x-icon path="ph.regular.arrow-right" />
                        </x-button>
                    @else
                        {{-- Empty summary state --}}
                        <h3 class="lk-payment-summary-title">{{ __('lk.payment_summary') }}</h3>
                        <div class="lk-payment-summary-empty">
                            <p>{{ __('lk.select_options_first') }}</p>
                        </div>
                    @endif
                @else
                    {{-- Empty summary state --}}
                    <h3 class="lk-payment-summary-title">{{ __('lk.payment_summary') }}</h3>
                    <div class="lk-payment-summary-empty">
                        <p>{{ __('lk.select_options_first') }}</p>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
