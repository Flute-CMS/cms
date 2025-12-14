@props(['type', 'identifier' => null])

@php
    $updateService = app(\Flute\Core\Update\Services\UpdateService::class);
    $hasUpdate = $updateService->hasUpdate($type, $identifier);
    $details = $updateService->getUpdateDetails($type, $identifier);
@endphp

@if ($hasUpdate)
    <div class="version-info" yoyo:ignore>
        <a href="{{ url('/admin/update') }}" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true"
            {{ $attributes->merge(['class' => 'version-update-link']) }}
            title="{{ __('admin-update.available') }} {{ $details['version'] ?? '' }}">
            <x-icon path="ph.bold.arrow-circle-up-bold" />
            {{ __('admin-update.updates_available') }} v{{ $details['version'] ?? '' }}
        </a>
    </div>
@endif
