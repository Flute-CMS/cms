@if ($type === 'percentage')
    <span class="badge accent">{{ __('admin-payment.type.percentage') }}</span>
@else
    <span class="badge primary">{{ __('admin-payment.type.fixed') }}</span>
@endif 