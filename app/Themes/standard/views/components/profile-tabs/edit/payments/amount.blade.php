<div class="d-flex flex-column">
    <strong>{{ number_format($amount, 2) }} {{ $currency }}</strong>
    @if(isset($originalAmount) && $originalAmount != $amount)
        <small class="text-muted text-decoration-line-through">{{ number_format($originalAmount, 2) }} {{ $currency }}</small>
    @endif
</div> 