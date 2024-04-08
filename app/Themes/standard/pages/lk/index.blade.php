@extends(tt('layout.blade.php'))

@push('header')
    @at(tt('assets/styles/pages/lk/index.scss'))
@endpush

@section('title')
    {{ !empty(page()->title) ? page()->title : __('lk.page.title') }}
@endsection

@push('content')
    @navbar
    <div class="container">
        @navigation
        @breadcrumb
        @editor

        @stack('container')

        <div class="lk">
            @if (sizeof($payments) > 1)
                <div class="lk-gateways">
                    <h3 class="lk-header active">@t('lk.page.choose_gateway')</h3>

                    @if (sizeof($currencies) > 1)
                        <div class="custom-select mb-3">
                            <button class="select-button" role="combobox" aria-labelledby="select button"
                                aria-haspopup="listbox" aria-expanded="false" aria-controls="select-dropdown">
                                <span class="selected-value">{{ $currencies[0]->code }}</span>
                                <span class="arrow"></span>
                            </button>
                            <ul class="select-dropdown" role="listbox" id="select-dropdown">
                                @foreach ($currencies as $key => $item)
                                    <li role="option">
                                        <input type="radio" id="{{ $item->code }}" name="social-account"
                                            @if ($key === 0) checked @endif />
                                        <label for="{{ $item->code }}">{{ strtoupper($item->code) }}</label>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (sizeof($payments) > 1)
                        <div class="lk-gateways-content">
                            @foreach ($payments as $key => $val)
                                <button data-selectgateway="{{ $key }}" class="gateway"
                                    @if (!$currencies[0]->hasPaymentByKey($key)) style="display: none;" @endif>
                                    <img src="@asset('assets/img/payments/' . $key . '.webp')" alt="{{ $key }}">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
            <div class="lk-result inactive @if (sizeof($payments) > 1) lk-result-line @else center-block @endif">
                <h3 class="lk-header">@t('lk.page.put_amount_and_promo')</h3>

                <div class="lk-result-content">
                    <div class="input-lk">
                        <label for="amount">@t('lk.page.enter_amount')</label>
                        <input type="number" name="amount"
                            @if (request()->input('sum') && (int) request()->input('sum') >= config('lk.min_amount')) value="{{ request()->input('sum') }}" @endif
                            placeholder="@t('lk.page.placeholder_amount')" id="amount">
                        <div id="messageAmount" class="message"></div>
                    </div>
                    <div class="input-lk">
                        <label for="promo">@t('lk.page.enter_promo')</label>
                        <input type="text" class="with--btn" placeholder="@t('lk.page.placeholder_promo')" name="promo"
                            id="promo">
                        <button class="btn-absolute">@t('lk.page.apply')</button>
                        <span id="messagePromo" class="message"> </span>
                    </div>

                    <div class="lk-result-content-info" id="amount_to_pay" data-currency="{{ config('lk.currency_view') }}">
                        <p>@t('lk.page.you_pay'):</p>
                        <span></span>
                    </div>

                    <div class="lk-result-content-info" id="amount_result" data-currency="{{ config('lk.currency_view') }}">
                        <p>@t('lk.page.you_receive'):</p>
                        <span></span>
                    </div>

                    @if (config('lk.oferta_view'))
                        <div class="form-checkbox">
                            <input class="form-check-input" name="agree" type="checkbox" id="agree">
                            <label class="form-check-label" for="agree">
                                @t('lk.page.agree_terms') <a href="{{ url('/oferta') }}">@t('lk.page.user_agreement')</a>
                            </label>
                        </div>
                    @endif

                    <button id="buy_btn" class="btn size-s primary" disabled>@t('lk.page.recharge')</button>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('footer')
    <script>
        @if (sizeof($payments) === 1)
            let selectedGatewayInit = "{{ array_key_first($payments) }}";
        @endif

        @if (!empty($currencies))
            let selectedCurrency = '{{ $currencies[0]->code }}';
            let selectedGateway = null;
            let currencyExchangeRates = {!! json_encode($currencyExchangeRates) !!};
            let currencyGateways = {!! json_encode($currencyGateways) !!};
            let currencyMinimumAmounts = {!! json_encode($currencyMinimumAmounts) !!};
        @endif
    </script>

    @at(tt('assets/js/pages/lk.js'))
@endpush

@footer
