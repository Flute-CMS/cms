@php
    /**
     * @var string $currency
     */
    $currency;
@endphp

<div class="lk-payment-container">
    <div class="lk-payment-layout">
        <div class="lk-payment-main">
            <form class="lk-form" id="payment-form" yoyo:post="purchase" yoyo:on="submit" aria-live="polite" role="form"
                aria-label="{{ __('lk.payment_form') }}">

                <div class="lk-payment-section" id="currency-section">
                    <h3 class="lk-payment-section-title">{{ __('lk.select_currency') }}</h3>

                    @if (count($currencies) > 1)
                        <div class="lk-payment-section-content">
                            <x-forms.field class="lk-field">
                                <ul class="lk-currencies" role="radiogroup" aria-label="{{ __('lk.select_currency') }}">
                                    @foreach ($currencies as $code)
                                        <li class="lk-currencies-item">
                                            <input type="radio" id="currency__{{ $code }}" name="currency"
                                                value="{{ $code }}" @checked($currency === $code) yoyo
                                                aria-label="{{ __('lk.currency_option', ['code' => $code]) }}"
                                                data-noprogress />
                                            <label for="currency__{{ $code }}" tabindex="0">
                                                <span class="lk-currency-code">{{ $code }}</span>
                                                <span class="lk-currency-rate">1 {{ $code }} =
                                                    {{ $currencyExchangeRates[$code] }}
                                                    {{ config('lk.currency_view') }}</span>
                                            </label>
                                        </li>
                                    @endforeach
                                </ul>
                                @if (!$currency)
                                    <small class="lk-payment-hint">{{ __('lk.select_currency_prompt') }}</small>
                                @endif
                            </x-forms.field>
                        </div>
                    @else
                        <div class="lk-payment-section-content">
                            <div class="lk-payment-selected">
                                <div class="content">
                                    <span class="lk-currency-code">{{ $currency }}</span>
                                    <span class="lk-currency-rate">1:{{ $currencyExchangeRates[$currency] }}
                                        {{ config('lk.currency_view') }}</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="lk-payment-section" id="gateway-section">
                    @if (!empty($currencyGateways[$currency]))
                        <h3 class="lk-payment-section-title">{{ __('lk.select_gateway') }}</h3>
                    @endif
                    @if (!empty($currencyGateways[$currency]))
                        <div class="lk-payment-section-content">
                            @if (count($currencyGateways[$currency]) > 1)
                                <x-forms.field class="lk-field">
                                    <ul class="lk-gateways" role="radiogroup"
                                        aria-label="{{ __('lk.select_gateway') }}">
                                        @foreach ($currencyGateways[$currency] as $key => $gatewayObject)
                                            @php
                                                $gatewayName = $gatewayObject['name'];
                                                $gatewayImage = $gatewayObject['image'];
                                            @endphp

                                            <li class="lk-gateways-item">
                                                <input type="radio" id="gateway__{{ $key }}" name="gateway"
                                                    value="{{ $key }}" @checked($gateway === $key) yoyo
                                                    aria-label="{{ __('lk.gateway_option', ['name' => $gatewayName]) }}"
                                                    data-noprogress />
                                                <label for="gateway__{{ $key }}" tabindex="0">
                                                    <div class="lk-gateway-info">
                                                        <h5>{{ $gatewayName }}</h5>
                                                    </div>
                                                    <img src="{{ asset($gatewayImage ?? 'assets/img/payments/' . $key . '.webp') }}"
                                                        alt="{{ $gatewayName }}" loading="lazy" width="80"
                                                        height="100" />
                                                </label>
                                            </li>
                                        @endforeach
                                    </ul>
                                    @if (!$gateway)
                                        <small class="lk-payment-hint">{{ __('lk.select_gateway_prompt') }}</small>
                                    @endif
                                </x-forms.field>
                            @else
                                @php
                                    $gatewayKey = array_key_first($currencyGateways[$currency]);
                                    $gatewayObject = $currencyGateways[$currency][$gatewayKey];
                                    $gatewayName = $gatewayObject['name'];
                                    $gatewayImage = $gatewayObject['image'];
                                @endphp
                                <div class="lk-payment-selected">
                                    <div class="content">
                                        <span class="lk-gateway-name">{{ $gatewayName }}</span>
                                    </div>
                                    <img src="{{ asset($gatewayImage ?? 'assets/img/payments/' . $gatewayKey . '.webp') }}"
                                        alt="{{ $gatewayName }}" loading="lazy" width="80" height="100" />
                                    <input type="hidden" name="gateway" value="{{ $gatewayKey }}" />
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="lk-payment-section-content">
                            <div class="lk-payment-error">
                                {{ __('lk.no_gateways_for_currency') }}
                            </div>
                        </div>
                    @endif
                </div>

                @if ($currency && !empty($currencyGateways[$currency]) && $gateway)
                    <div class="lk-payment-section" id="amount-section">
                        <h3 class="lk-payment-section-title">{{ __('lk.top_up_amount') }}</h3>

                        <div class="lk-payment-section-content">
                            <x-forms.field yoyo yoyo:on="input changed delay:500ms" data-noprogress class="lk-field">
                                <div class="lk-amount-input-wrapper">
                                    <x-fields.input type="number" name="amount" id="amount"
                                        min="{{ $currencyMinimumAmounts[$currency] }}" step="0.01"
                                        value="{{ $amount }}" required placeholder="{{ __('lk.enter_amount') }}"
                                        aria-describedby="amount-description">
                                        <x-slot:postPrefix>
                                            <span class="lk-amount-currency">{{ $currency }}</span>
                                        </x-slot:postPrefix>
                                    </x-fields.input>
                                </div>

                                <div class="lk-amount-info">
                                    <small class="lk-payment-hint" id="amount-description">
                                        {{ __('lk.min_amount_info', ['amount' => $currencyMinimumAmounts[$currency], 'currency' => $currency]) }}
                                    </small>
                                </div>
                            </x-forms.field>
                        </div>
                    </div>
                @endif

                @if ($amount)
                    <div class="lk-payment-section" id="promo-section">
                        <h3 class="lk-payment-section-title">{{ __('lk.promo_code_label') }}</h3>

                        <div class="lk-payment-section-content">
                            <x-forms.field yoyo yoyo:on="input delay:800ms changed blur" yoyo:post="validatePromo" data-noprogress class="lk-field">
                                <div class="lk-promo-input-wrapper">
                                    <x-fields.input type="text" name="promoCode" id="promoCode"
                                        value="{{ $promoCode }}" placeholder="{{ __('lk.enter_promo_code') }}"
                                        aria-describedby="promo-status">
                                        <x-slot:postPrefix>
                                            @if ($promoCode)
                                                <button type="button" class="lk-promo-clear"
                                                    data-tooltip="{{ __('lk.clear_promo') }}"
                                                    onclick="document.getElementById('promoCode').value = ''; document.getElementById('promoCode').dispatchEvent(new Event('changed'));">
                                                    <x-icon path="ph.regular.x" />
                                                </button>
                                            @endif
                                        </x-slot:postPrefix>
                                    </x-fields.input>
                                </div>
                            </x-forms.field>
                        </div>
                    </div>
                @endif
            </form>
        </div>

        <div class="lk-payment-summary" id="payment-summary">
            <h3 class="lk-payment-summary-title">{{ __('lk.payment_summary') }}</h3>

            <ul class="lk-details">
                <li class="lk-details-item">
                    <span>{{ __('lk.base_amount') }}</span>
                    <span>{{ $amount ?? 0 }} {{ $currency }}</span>
                </li>

                @if ($promoIsValid && $promoDetails)
                    <li class="lk-details-item lk-details-promo">
                        <span>
                            @if ($promoDetails['type'] === 'amount')
                                {{ __('lk.bonus') }}
                            @else
                                {{ __('lk.discount') }}
                            @endif
                        </span>
                        <span class="lk-promo-value">
                            @if ($promoDetails['type'] === 'amount')
                                +{{ $promoDetails['value'] }} {{ $currency }}
                            @elseif ($promoDetails['type'] === 'percentage')
                                -{{ $promoDetails['value'] }}%
                            @endif
                        </span>
                    </li>
                @endif

                <li class="lk-details-item lk-details-total">
                    <span>{{ __('lk.to_pay') }}</span>
                    <span>{{ $amountToPay ?? 0 }} {{ $currency }}</span>
                </li>

                <li class="lk-details-item lk-details-receive">
                    <span>{{ __('lk.you_will_receive') }}</span>
                    <span class="lk-receive-amount">{{ $amountToReceive ?? 0 }}
                        {{ config('lk.currency_view') }}</span>
                </li>
            </ul>

            @if ($amount && config('lk.oferta_view'))
                <div class="lk-payment-terms-section" id="terms-section">
                    <div class="lk-payment-section-content">
                        <x-forms.field class="lk-field lk-terms-field">
                            <x-fields.checkbox name="agree" id="agree" checked="{{ $agree }}"
                                data-noprogress yoyo aria-describedby="terms-link">
                                <x-slot:label>
                                    {{ __('lk.agree_terms') }}
                                    <x-link href="{{ url(config('lk.oferta_url', '/agreenment')) }}" id="terms-link"
                                        target="_blank" rel="noopener">
                                        {{ __('lk.terms_of_offer') }}
                                    </x-link>
                                </x-slot:label>
                            </x-fields.checkbox>
                        </x-forms.field>
                    </div>
                </div>
            @endif

            <x-button size="large" class="lk-payment-submit" withLoading submit form="payment-form"
                :disabled="($promoCode && !$promoIsValid) || (config('lk.oferta_view') && $agree === false)"
                aria-label="{{ __('lk.top_up_button', [':amount' => $amountToReceive, ':currency_view' => config('lk.currency_view')]) }}">
                <span>{{ __('lk.top_up_button', [':amount' => $amountToReceive, ':currency_view' => config('lk.currency_view')]) }}</span>
            </x-button>
        </div>
    </div>
</div>
