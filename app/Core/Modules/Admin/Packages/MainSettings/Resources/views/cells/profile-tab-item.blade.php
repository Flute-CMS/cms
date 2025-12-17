<div class="d-flex align-items-center">
    @if ($icon)
        <x-icon path="{{ $icon }}" class="me-2" style="font-size: var(--h5)" />
    @endif
    <div style="margin-left: 10px">
        <div class="fw-medium" style="line-height: 1.25">{{ __($title) }}</div>
        <small class="text-muted">{{ $path }}</small>
    </div>
</div>

