@php
    $icon = $icon ?? 'ph.regular.smiley-melting';
    $title = $title ?? __('admin.empty.title');
    $description = $description ?? __('admin.empty.description');
    $height = $height ?? '100';
@endphp

<div class="widget-empty text-center d-flex flex-column justify-content-center align-items-center" style="min-height: {{ $height ?? '100' }}px;">
    <x-icon path="ph.regular.{{ $icon }}" class="mb-3" style="font-size: var(--h1); opacity: 0.5;" />
    <h5 class="text-muted mb-0">{{ $title }}</h5>
    @if ($description)
        <p class="text-muted">{{ $description }}</p>
    @endif
</div>
