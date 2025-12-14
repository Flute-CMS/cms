@props(['enabled'])

@if ($enabled)
    <span class="badge success">{{ __('admin-payment.status.active') }}</span>
@else
    <span class="badge error">{{ __('admin-payment.status.inactive') }}</span>
@endif