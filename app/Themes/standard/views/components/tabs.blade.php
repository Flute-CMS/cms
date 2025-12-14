@props([
    'name' => '',
    'scrollable' => true,
])

@php
    $name = preg_replace('/[^\w-]/', '_', $name);
    $scrollable = filter_var($scrollable, FILTER_VALIDATE_BOOLEAN);
@endphp

<div {{ $attributes->merge(['class' => 'tabs-container' . ($scrollable ? ' scrollable-tabs' : '')]) }} data-tabs-id="tab__{{ $name }}">
    <div class="tabs-nav-wrapper">
        <ul class="{{ $name }}-headings tabs-nav" role="tablist">
            {{ $headings }}
            <div class="underline" hx-preserve></div>
        </ul>
    </div>
    {{ $slot }}
</div>
