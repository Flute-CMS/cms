@props(['gateway'])

<div class="payment__image">
    @if ($gateway->image)
        <img src="{{ asset($gateway->image) }}" alt="{{ $gateway->name }}" />
    @else
        <img src="{{ asset('assets/img/payments/' . $gateway->adapter . '.webp') }}" alt="{{ $gateway->name }}" />
    @endif
</div>
