<div>
    <span class="badge info">{{ $promoCode }}</span>
    <div class="small">
        @if($type == 'percentage')
            -{{ number_format($value, 0) }}%
        @else
            -{{ number_format($value, 2) }} {{ config('lk.currency_view') }}
        @endif
    </div>
</div> 