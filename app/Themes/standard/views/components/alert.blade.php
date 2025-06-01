@props([
    'type' => 'info',
    'icon' => null,
    'withClose' => true,
    'onlyBorders' => false
])
@php
    $iconPath =
        $icon ??
        match ($type) {
            'success' => 'ph.regular.check-circle',
            'error' => 'ph.regular.x-circle',
            'warning' => 'ph.regular.warning-circle',
            'info' => 'ph.regular.info',
            default => 'ph.regular.info',
        };

    $borderClass = $onlyBorders ? 'border' : '';
@endphp

<div {{ $attributes->merge(['class' => "alert alert-{$type} {$borderClass}", 'role' => 'alert']) }}>
    <x-icon path="{{ $iconPath }}" class="alert-icon" />
<div>
    {{ $slot }}
    </div>
    @if (filter_var($withClose, FILTER_VALIDATE_BOOLEAN))
        <button type="button" class="alert-close" aria-label="Close" onclick="this.parentElement.style.display = 'none';">
            <x-icon path="ph.bold.x-bold" />
        </button>
    @endif
</div>