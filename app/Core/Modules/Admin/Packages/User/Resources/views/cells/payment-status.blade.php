@if ($invoice->isPaid)
    <span class="badge success">{{ __('admin-users.status.paid') }}</span>
@else
    <span class="badge warning">{{ __('admin-users.status.unpaid') }}</span>
@endif
