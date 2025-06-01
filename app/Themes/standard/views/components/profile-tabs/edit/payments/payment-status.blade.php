@if($isPaid)
    <span class="badge success">{{ __('profile.edit.payments.status.paid') }}</span>
    @if($paidAt)
        <small class="d-block text-muted mt-1">
            {{ $paidAt instanceof \DateTimeInterface ? $paidAt->format(default_date_format()) : date(default_date_format(), strtotime($paidAt)) }}
        </small>
    @endif
@else
    <span class="badge warning">{{ __('profile.edit.payments.status.pending') }}</span>
@endif