<x-card class="promo-codes">
    <x-slot name="header">
        <div class="promo-codes__header">
            <h5>{{ __('widgets.active_promo_codes') }}</h5>
            @if (!empty($promoCodes))
                <span class="promo-codes__count">{{ count($promoCodes) }}</span>
            @endif
        </div>
    </x-slot>

    @if (!empty($promoCodes))
        <div class="promo-codes__list">
            @foreach ($promoCodes as $promoCode)
                <div class="promo-codes__item">
                    <div class="promo-codes__code">
                        <span class="promo-codes__value">{{ $promoCode->code }}</span>
                        <button type="button"
                            class="promo-codes__copy"
                            onclick="copyToClipboard('{{ $promoCode->code }}'); notyf.success('{{ __('widgets.promo_code_copy_success') }}')"
                            data-tooltip="{{ __('def.copy') }}">
                            <x-icon path="ph.regular.copy" />
                        </button>
                    </div>
                    <div class="promo-codes__meta">
                        <span class="promo-codes__discount">
                            @if ($promoCode->type === 'percentage')
                                -{{ $promoCode->value }}%
                            @else
                                -{{ $promoCode->value }} {{ config('lk.currency_view') }}
                            @endif
                        </span>
                        @if ($promoCode->expires_at)
                            <span class="promo-codes__expires">
                                {{ carbon($promoCode->expires_at)->diffForHumans() }}
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="promo-codes__empty">
            <x-icon path="ph.regular.ticket" />
            <span>{{ __('widgets.no_promo_codes') }}</span>
        </div>
    @endif
</x-card>
