<div class="d-flex align-items-center gap-2">
    @if($isPaid)
        <span class="badge success" style="flex-shrink: 0;">{{ __('profile.edit.payments.status.paid') }}</span>
    @else
        <span class="badge warning" style="flex-shrink: 0;">{{ __('profile.edit.payments.status.pending') }}</span>
    @endif
    <small style="color: var(--text-500); white-space: nowrap;">{{ $date }} {{ $time }}</small>
</div>
