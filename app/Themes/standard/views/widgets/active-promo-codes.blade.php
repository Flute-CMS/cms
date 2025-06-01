<x-card class="promo-codes" withoutPadding>
    <x-slot name="header">
        <div class="promo-codes-header">
            <h5>
                <span class="promo-codes-icon">
                    <x-icon path="ph.regular.ticket" />
                </span>
                {{ __('widgets.active_promo_codes') }}
            </h5>
            <small class="text-muted promo-codes-count">{{ count($promoCodes) }}</small>
        </div>
    </x-slot>
    <div class="promo-codes-content">
        @if (! empty($promoCodes))
            <div class="promo-code-list">
                @foreach ($promoCodes as $promoCode)
                    <div class="promo-code-item">
                        <div class="promo-code-code">
                            <span>{{ $promoCode->code }}</span>
                            <button class="promo-code-copy"
                                onclick="copyToClipboard('{{ $promoCode->code }}');notyf.success('{{ __('widgets.promo_code_copy_success') }}')"
                                data-tooltip="{{ __('def.copy') }}">
                                <x-icon path="ph.regular.copy" />
                            </button>
                        </div>
                        <div class="promo-code-info">
                            <div class="promo-code-discount">
                                @if ($promoCode->type === 'percentage')
                                    {{ __('widgets.discount.percentage', ['value' => $promoCode->value]) }}
                                @else
                                    {{ __('widgets.discount.amount', ['value' => $promoCode->value, 'currency' => config('lk.currency_view')]) }}
                                @endif
                            </div>
                            <div class="promo-code-expiry">
                                <small class="text-muted">
                                    {{ __('widgets.expires') }}:
                                    {{ $promoCode->expires_at ? $promoCode->expires_at->format('d.m.Y') : __('def.never') }}
                                </small>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="promo-codes-empty">
                <x-icon path="ph.regular.ticket" />
                <p>{{ __('widgets.no_promo_codes') }}</p>
            </div>
        @endif
    </div>
</x-card>