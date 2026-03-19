<div class="d-flex flex-column">
    <strong>{{ number_format($amount, 2) }} {{ $currency }}</strong>
    @if(isset($originalAmount) && $originalAmount != $amount)
        <small style="color: var(--text-500); text-decoration: line-through;">{{ number_format($originalAmount, 2) }} {{ $currency }}</small>
    @endif
    @if(!empty($promoCode))
        <small style="color: var(--accent);">
            {{ $promoCode }}
            ({{ $promoType === 'percentage' ? '-' . number_format($promoValue, 0) . '%' : '-' . number_format($promoValue, 2) }})
        </small>
    @endif
</div>
