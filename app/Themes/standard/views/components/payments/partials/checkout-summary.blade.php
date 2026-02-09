{{-- Promo Code --}}
<div class="lk-promo-wrap">
    <div class="lk-promo" data-lk-promo>
        <x-icon path="ph.regular.ticket" class="lk-promo__icon" />
        <input type="text" name="promoCode" id="lk-promo"
            class="lk-promo__input"
            placeholder="{{ __('lk.enter_promo_code') }}" />
        <span class="lk-promo__badge" data-lk-promo-badge style="display:none"></span>
    </div>
</div>

{{-- Additional fields from modules --}}
{!! $additionalFields ?? '' !!}

{{-- Receipt (built by JS) --}}
<div class="lk-receipt" data-lk-receipt style="display:none"></div>

{{-- Terms --}}
@if (config('lk.oferta_view'))
    <div class="lk-terms">
        <x-fields.checkbox name="agree" id="lk-agree" aria-describedby="terms-link">
            <x-slot:label>
                {{ __('lk.agree_terms') }}
                <x-link type="accent"
                    href="{{ url(config('lk.oferta_url', '/agreenment')) }}"
                    id="terms-link" target="_blank" rel="noopener">
                    {{ __('lk.terms_of_offer') }}
                </x-link>
            </x-slot:label>
        </x-fields.checkbox>
    </div>
@endif

{{-- Submit --}}
<button type="submit" class="btn btn--primary lk-pay-btn" id="lk-submit" disabled>
    <span data-lk-btn-text>{{ __('lk.top_up_button') }}</span>
    <x-icon path="ph.regular.arrow-right" />
    <span class="lk-pay-btn__loader" style="display:none"></span>
</button>
