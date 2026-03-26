@php
    $badgeClass = match($type) {
        'topup' => 'success',
        'purchase' => 'error',
        'refund' => 'info',
        'admin' => $amount >= 0 ? 'success' : 'warning',
        default => '',
    };
    $icon = match($type) {
        'topup' => 'ph.regular.arrow-circle-up',
        'purchase' => 'ph.regular.shopping-cart',
        'refund' => 'ph.regular.arrow-counter-clockwise',
        'admin' => 'ph.regular.shield-star',
        default => 'ph.regular.currency-circle-dollar',
    };
@endphp
<span class="badge {{ $badgeClass }}" style="gap: 4px;">
    <x-icon path="{{ $icon }}" style="font-size: 14px;" />
    {{ __('profile.edit.balance_history.types.' . $type) }}
</span>
