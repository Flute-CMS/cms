@props([
    'name' => '',
    'pills' => false,
    'sticky' => true,
])

@php
    $name = preg_replace('/[^\w-]/', '_', $name);
@endphp

<div {{ $attributes->merge(['class' => 'tabs-container' . ($pills ? ' pills' : ''), 'data-sticky' => $sticky ? 'true' : 'false']) }}
    data-tabs-id="tab__{{ $name }}">
    <div class="tabs-nav-scroll">
        <ul class="{{ $name }}-headings tabs-nav">
            {{ $headings }}
            <div class="underline" hx-swap="none"></div>
        </ul>
    </div>

    {{ $slot }}
</div>
