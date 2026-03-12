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

    $jsConfig = [
        'currencies' => $currencies,
        'gateways' => $currencyGateways,
        'exchangeRates' => $currencyExchangeRates,
        'minimumAmounts' => $currencyMinimumAmounts,
        'currencyView' => config('lk.currency_view'),
        'ofertaView' => (bool) config('lk.oferta_view'),
        'maxAmount' => config('lk.max_single_amount', 1000000),
        'mode' => 'page',
        'presets' => [
            'RUB' => [100, 250, 500, 1000, 2500, 5000],
            'USD' => [1, 5, 10, 25, 50, 100],
            'EUR' => [1, 5, 10, 25, 50, 100],
            'UAH' => [50, 100, 200, 500, 1000, 2500],
            'KZT' => [500, 1000, 2000, 5000, 10000, 25000],
            '_default' => [100, 250, 500, 1000, 2500, 5000],
        ],
        'i18n' => [
            'min_amount_info' => __('lk.min_amount_info', ['amount' => ':amount', 'currency' => ':currency']),
            'base_amount' => __('lk.base_amount'),
            'gateway_fee' => __('lk.gateway_fee'),
            'gateway_fee_tooltip' => __('lk.gateway_fee_tooltip'),
            'gateway_bonus' => __('lk.gateway_bonus'),
            'you_will_receive' => __('lk.you_will_receive'),
            'to_pay' => __('lk.to_pay'),
            'bonus' => __('lk.bonus'),
            'discount' => __('lk.discount'),
            'top_up_button' => __('lk.top_up_button'),
            'preset_amounts' => __('lk.preset_amounts'),
            'select_currency' => __('lk.select_currency'),
            'select_gateway' => __('lk.select_gateway'),
            'promo_applied_discount' => __('lk.promo_applied_discount'),
            'promo_applied_bonus' => __('lk.promo_applied_bonus'),
            'promo_invalid' => __('lk.promo_invalid'),
            'redirecting' => __('lk.redirecting'),
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
        <form id="lk-form" class="lk-form" aria-label="{{ __('lk.payment_form') }}">
            <div class="lk-grid">
                {{-- ── Left column: Steps ── --}}
                <div class="lk-grid__main">

                    {{-- Step 1: Amount --}}
                    <section class="lk-card">
                        <div class="lk-card__head">
                            <div class="lk-card__title">
                                <span class="lk-step-num">1</span>
                                <span>{{ __('lk.top_up_amount') }}</span>
                            </div>

                            @if (!$singleCurrency)
                                <div class="lk-currency-bar" role="radiogroup"
                                    aria-label="{{ __('lk.select_currency') }}">
                                    @foreach ($currencies as $code)
                                        <div class="lk-currency-bar__item">
                                            <input type="radio" id="currency__{{ $code }}" name="currency"
                                                value="{{ $code }}" @checked($currency === $code) />
                                            <label for="currency__{{ $code }}">{{ $code }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <input type="hidden" name="currency" value="{{ $currency }}" />
                            @endif
                        </div>

                        <div class="lk-amount-field">
                            <input type="text" name="amount" id="lk-amount"
                                inputmode="decimal" autocomplete="off"
                                placeholder="0" />
                            <span class="lk-amount-field__cur" data-lk-currency-label>{{ $currency }}</span>
                        </div>

                        <div class="lk-presets" role="group" data-lk-presets
                            aria-label="{{ __('lk.preset_amounts') }}"></div>

                        <p class="lk-hint" data-lk-hint></p>
                    </section>

                    {{-- Step 2: Payment method --}}
                    <section class="lk-card">
                        <div class="lk-card__head">
                            <div class="lk-card__title">
                                <span class="lk-step-num">2</span>
                                <span>{{ __('lk.select_gateway') }}</span>
                            </div>
                        </div>

                        @foreach ($currencyGateways as $currCode => $gateways)
                            @php $isCurrent = $currCode === $currency; @endphp
                            <div class="lk-gw-grid {{ count($gateways) === 1 ? 'is-single' : '' }}"
                                data-lk-gateways="{{ $currCode }}"
                                role="radiogroup"
                                @unless($isCurrent) style="display:none" @endunless>

                                @foreach ($gateways as $key => $gw)
                                    @php
                                        $isSelected = ($isCurrent && $defaultGateway === $key) || count($gateways) === 1;
                                        $hasFee = ($gw['fee'] ?? 0) > 0;
                                        $hasBonus = ($gw['bonus'] ?? 0) > 0;
                                    @endphp
                                    <label class="lk-gw-card" for="gw__{{ $currCode }}_{{ $key }}">
                                        <input type="radio" id="gw__{{ $currCode }}_{{ $key }}"
                                            name="gateway" value="{{ $key }}"
                                            @checked($isSelected) @disabled(!$isCurrent)
                                            data-fee="{{ $gw['fee'] ?? 0 }}"
                                            data-bonus="{{ $gw['bonus'] ?? 0 }}"
                                            data-min="{{ $gw['minimum_amount'] ?? '' }}" />

                                        <span class="lk-gw-card__icon">
                                            @if (!empty($gw['image']))
                                                <img src="{{ asset($gw['image']) }}" alt="{{ $gw['name'] }}"
                                                    loading="lazy" />
                                            @else
                                                <x-icon path="ph.regular.credit-card" />
                                            @endif
                                        </span>

                                        <span class="lk-gw-card__body">
                                            <span class="lk-gw-card__name">{{ $gw['name'] }}</span>
                                            @if (!empty($gw['description']))
                                                <span class="lk-gw-card__desc">{{ $gw['description'] }}</span>
                                            @endif
                                        </span>

                                        @if ($hasBonus)
                                            <span class="lk-gw-card__tag lk-gw-card__tag--bonus"
                                                data-tooltip="{{ __('lk.gateway_bonus_tooltip', ['value' => $gw['bonus']]) }}"
                                                data-tooltip-placement="top">+{{ $gw['bonus'] }}%</span>
                                        @elseif ($hasFee)
                                            <span class="lk-gw-card__tag lk-gw-card__tag--fee"
                                                data-tooltip="{{ __('lk.gateway_fee_tooltip') }}"
                                                data-tooltip-placement="top">+{{ $gw['fee'] }}%</span>
                                        @endif
                                    </label>
                                @endforeach
                            </div>

                            @if (count($gateways) === 1)
                                <input type="hidden" data-lk-gateway-hidden="{{ $currCode }}"
                                    name="{{ $isCurrent ? 'gateway' : '' }}"
                                    value="{{ array_key_first($gateways) }}"
                                    @unless($isCurrent) disabled @endunless />
                            @endif
                        @endforeach

                        @if (!$hasGateways)
                            <div class="lk-gw-empty">{{ __('lk.no_gateways_for_currency') }}</div>
                        @endif
                    </section>

                    {{-- Gateway-specific fields --}}
                    @if (!empty($gatewayFields))
                        @foreach ($gatewayFields as $gwAdapter => $gwFieldsHtml)
                            <div class="lk-gw-fields" data-lk-gw-fields="{{ $gwAdapter }}"
                                @unless($gwAdapter === $defaultGateway) style="display:none" @endunless>
                                {!! $gwFieldsHtml !!}
                            </div>
                        @endforeach
                    @endif
                </div>

                {{-- ── Right column: Summary ── --}}
                <div class="lk-grid__aside">

                    {{-- Balance --}}
                    <div class="lk-balance-widget">
                        <span class="lk-balance-widget__label">{{ __('lk.your_balance') }}</span>
                        <div class="lk-balance-widget__value">
                            {{ number_format(user()->getCurrentUser()->balance, 2) }}
                            <span>{{ config('lk.currency_view', 'FC') }}</span>
                        </div>
                        <div class="lk-balance-widget__bg">
                            <x-icon path="ph.bold.wallet-bold" />
                        </div>
                    </div>

                    {{-- Checkout --}}
                    <div class="lk-checkout">
                        <h3 class="lk-checkout__title">
                            <x-icon path="ph.regular.receipt" />
                            {{ __('lk.to_pay') }}
                        </h3>

                        {{-- Promo --}}
                        <div class="lk-promo" data-lk-promo>
                            <div class="lk-promo__field">
                                <input type="text" name="promoCode" id="lk-promo"
                                    class="lk-promo__input"
                                    placeholder="{{ __('lk.promo_code_label') }}"
                                    autocomplete="off" />
                                <button type="button" class="lk-promo__btn" data-lk-promo-btn style="display:none"
                                    data-label-apply="{{ __('lk.promo_apply') }}"
                                    data-label-clear="{{ __('lk.promo_clear') }}">
                                    {{ __('lk.promo_apply') }}
                                </button>
                            </div>
                            <p class="lk-promo__message" data-lk-promo-msg></p>
                        </div>

                        {{-- Receipt --}}
                        <div class="lk-receipt" data-lk-receipt style="display:none"></div>

                        {{-- Terms --}}
                        @if (config('lk.oferta_view'))
                            <div class="lk-terms">
                                <x-fields.checkbox name="agree" id="lk-agree">
                                    <x-slot:label>
                                        {{ __('lk.agree_terms') }}
                                        <a href="{{ url(config('lk.oferta_url', '/agreenment')) }}"
                                            target="_blank" rel="noopener">{{ __('lk.terms_of_offer') }}</a>
                                    </x-slot:label>
                                </x-fields.checkbox>
                            </div>
                        @endif

                        {{-- Submit --}}
                        <x-button type="primary" :submit="true" :disabled="true" id="lk-submit"
                            class="lk-submit" yoyo:ignore>
                            <span data-lk-btn-text>{{ __('lk.top_up_button') }}</span>
                            <x-icon path="ph.regular.arrow-right" />
                            <span class="lk-submit__loader" style="display:none"></span>
                        </x-button>

                        <p class="lk-disclaimer">{{ __('lk.gateway_disclaimer') }}</p>
                    </div>
                </div>
            </div>
        </form>

        {{-- JS Templates --}}
        <template data-lk-tpl="preset">
            <button type="button" class="lk-preset"></button>
        </template>

        <template data-lk-tpl="receipt-row">
            <div class="lk-receipt__row">
                <span data-label></span>
                <span data-value></span>
            </div>
        </template>

        <template data-lk-tpl="receipt-row-green">
            <div class="lk-receipt__row lk-receipt__row--green">
                <span data-label></span>
                <span data-value></span>
            </div>
        </template>

        <template data-lk-tpl="receipt-total">
            <div class="lk-receipt__total">
                <span data-label></span>
                <span data-value></span>
            </div>
        </template>

        <template data-lk-tpl="redirect">
            <div class="lk-redirect-overlay">
                <div class="lk-redirect-overlay__spinner"></div>
                <p class="lk-redirect-overlay__text">{{ __('lk.redirecting') }}</p>
            </div>
        </template>

        <script src="@asset('assets/js/lk-payment.js')" defer></script>
    @endif
</div>
