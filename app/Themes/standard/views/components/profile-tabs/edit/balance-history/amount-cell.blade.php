<div class="d-flex flex-column">
    <strong class="d-flex align-items-center gap-1" style="color: {{ $amount >= 0 ? 'var(--success)' : 'var(--error)' }};">
        <x-icon path="{{ $amount >= 0 ? 'ph.bold.arrow-up-bold' : 'ph.bold.arrow-down-bold' }}" style="font-size: 12px;" />
        {{ $amount >= 0 ? '+' : '' }}{{ number_format($amount, 2) }} {{ $currency }}
    </strong>
    <small style="color: var(--text-500);">{{ number_format($balanceAfter, 2) }} {{ $currency }}</small>
</div>
