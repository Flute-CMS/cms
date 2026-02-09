@if (!$singleCurrency)
    <div class="lk-currency-bar" role="radiogroup" aria-label="{{ __('lk.select_currency') }}">
        @foreach ($currencies as $code)
            <div class="lk-currency-bar__item">
                <input type="radio" id="currency__{{ $code }}" name="currency"
                    value="{{ $code }}" @checked($currency === $code)
                    aria-label="{{ __('lk.currency_option', ['code' => $code]) }}" />
                <label for="currency__{{ $code }}">{{ $code }}</label>
            </div>
        @endforeach
    </div>
@else
    <input type="hidden" name="currency" value="{{ $currency }}" />
@endif
