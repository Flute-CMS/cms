@if ($expired)
    <span class="badge error">{{ __('admin-payment.status.expired') }}</span>
@elseif ($usagesLeft <= 0)
    <span class="badge warning">{{ __('admin-payment.status.depleted') }}</span>
@else
    <span class="badge success">{{ __('admin-payment.status.active') }}</span>
@endif 