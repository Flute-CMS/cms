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
        <div class="card lk-form-card">
            <form id="lk-form" class="lk-form-page" aria-label="{{ __('lk.payment_form') }}">
                {{-- Currency picker --}}
                @if (!$singleCurrency)
                    <div class="lk-form-page__row">
                        <div class="lk-currency-bar" role="radiogroup" aria-label="{{ __('lk.select_currency') }}">
                            @foreach ($currencies as $code)
                                <div class="lk-currency-bar__item">
                                    <input type="radio" id="currency__{{ $code }}" name="currency"
                                        value="{{ $code }}" @checked($currency === $code) />
                                    <label for="currency__{{ $code }}">{{ $code }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <input type="hidden" name="currency" value="{{ $currency }}" />
                @endif

                {{-- Amount --}}
                <div class="lk-form-page__row">
                    <label class="lk-form-page__label" for="lk-amount">{{ __('lk.top_up_amount') }}</label>
                    <div class="lk-amount-field">
                        <input type="text" name="amount" id="lk-amount"
                            inputmode="decimal" autocomplete="off"
                            placeholder="{{ __('lk.enter_amount') }}" />
                        <span class="lk-amount-field__currency" data-lk-currency-label>{{ $currency }}</span>
                    </div>
                    <div class="lk-pills" role="group" data-lk-presets
                        aria-label="{{ __('lk.preset_amounts') }}"></div>
                    <p class="lk-amount-hint" data-lk-hint></p>
                </div>

                <div class="lk-form-page__divider"></div>

                {{-- Payment methods --}}
                <div class="lk-form-page__row">
                    <span class="lk-form-page__label">{{ __('lk.select_gateway') }}</span>

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

                                    <span class="lk-gw-card__img">
                                        @if (!empty($gw['image']))
                                            <img src="{{ asset($gw['image']) }}" alt="{{ $gw['name'] }}" loading="lazy" />
                                        @else
                                            <x-icon path="ph.regular.credit-card" />
                                        @endif
                                    </span>
                                    <span class="lk-gw-card__text">
                                        <span class="lk-gw-card__name">{{ $gw['name'] }}</span>
                                        <span class="lk-gw-card__fee">
                                            @if ($hasBonus)
                                                <span class="lk-gw-card__bonus">+{{ $gw['bonus'] }}%</span>
                                            @elseif ($hasFee)
                                                {{ $gw['fee'] }}%
                                            @endif
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        @if (count($gateways) === 1)
                            <input type="hidden" data-lk-gateway-hidden="{{ $currCode }}"
                                name="{{ $isCurrent ? 'gateway' : '' }}" value="{{ array_key_first($gateways) }}"
                                @unless($isCurrent) disabled @endunless />
                        @endif
                    @endforeach

                    @if (!$hasGateways)
                        <div class="lk-gateways-empty">{{ __('lk.no_gateways_for_currency') }}</div>
                    @endif
                </div>

                {{-- Additional fields from modules --}}
                @if (!empty($additionalFields))
                    <div class="lk-form-page__divider"></div>
                    <div class="lk-form-page__row">
                        {!! $additionalFields !!}
                    </div>
                @endif

                {{-- Promo --}}
                <div class="lk-form-page__divider"></div>
                <div class="lk-form-page__row">
                    <details class="lk-promo-details" data-lk-promo-details>
                        <summary>
                            <x-icon path="ph.regular.ticket" />
                            <span>{{ __('lk.enter_promo_code') }}</span>
                        </summary>
                        <div class="lk-promo-field" data-lk-promo>
                            <input type="text" name="promoCode" id="lk-promo"
                                placeholder="{{ __('lk.promo_code_label') }}" />
                            <span class="lk-promo-field__badge" data-lk-promo-badge style="display:none"></span>
                        </div>
                    </details>
                </div>

                {{-- Receipt --}}
                <div class="lk-receipt" data-lk-receipt style="display:none"></div>

                {{-- Terms + Submit --}}
                <div class="lk-form-page__row lk-form-page__footer">
                    @if (config('lk.oferta_view'))
                        <div class="lk-terms">
                            <x-fields.checkbox name="agree" id="lk-agree">
                                <x-slot:label>
                                    {{ __('lk.agree_terms') }}
                                    <x-link type="accent"
                                        href="{{ url(config('lk.oferta_url', '/agreenment')) }}"
                                        target="_blank" rel="noopener">
                                        {{ __('lk.terms_of_offer') }}
                                    </x-link>
                                </x-slot:label>
                            </x-fields.checkbox>
                        </div>
                    @endif

                    <button type="submit" class="btn btn-primary lk-submit" id="lk-submit" disabled>
                        <span data-lk-btn-text>{{ __('lk.top_up_button') }}</span>
                        <x-icon path="ph.regular.arrow-right" />
                        <span class="lk-submit__loader" style="display:none"></span>
                    </button>
                </div>
            </form>
        </div>

        <p class="lk-footnote">{{ __('lk.gateway_disclaimer') }}</p>

        <script src="@asset('assets/js/lk-payment.js')" defer></script>
    @endif
</div>
