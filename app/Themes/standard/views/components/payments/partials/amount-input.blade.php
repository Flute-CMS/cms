<div class="lk-amount">
    <div class="lk-amount__field">
        <input type="text" name="amount" id="lk-amount"
            inputmode="decimal" autocomplete="off"
            min="{{ $effectiveMinAmount }}" step="0.01"
            placeholder="0" required
            aria-describedby="amount-hint" />
        <span class="lk-amount__currency" data-lk-currency-label>{{ $currency }}</span>
    </div>

    <div class="lk-amount__row">
        <p class="lk-amount__hint" id="amount-hint" data-lk-hint></p>

        <div class="lk-amount__presets" role="group" data-lk-presets
            aria-label="{{ __('lk.preset_amounts') }}"></div>
    </div>
</div>
