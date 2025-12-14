@if(isset($gateway_image))
    <div class="d-flex align-items-center">
        <img src="{{ asset($gateway_image) }}" alt="{{ $gateway_name ?? $gateway }}" class="mr-2" style="height: 24px; width: auto;">
        <span>{{ $gateway_name ?? $gateway }}</span>
    </div>
@else
    {{ $gateway }}
@endif 