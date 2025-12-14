@props(['invoice'])

@if ($invoice->isPaid)
    <span class="badge success">{{ __('admin-payment.status.paid') }}</span>
@else
    <span class="badge warning">{{ __('admin-payment.status.unpaid') }}</span>
@endif
