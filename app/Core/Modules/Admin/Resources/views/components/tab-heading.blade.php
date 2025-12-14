@props([
    'label' => null,
    'active' => false,
    'disabled' => false,
    'name' => 'tab',
    'url' => null,
    'badge' => null,
    'shouldTrigger' => true,
    'withoutHtmx' => false,
])

@php
    $name = preg_replace('/[^\w-]/', '_', $name);
    $active = filter_var($active, FILTER_VALIDATE_BOOLEAN);
    $disabled = filter_var($disabled, FILTER_VALIDATE_BOOLEAN);

    $label = $label ?? $slot;
@endphp

<li class='tab-item {{ $active ? 'active' : '' }} {{ $disabled ? ' is-disabled' : '' }}'>
    <a data-tab-id="tab__{{ $name }}" @if ($withoutHtmx && $url) href="{{ $url }}" @endif
        @if (!empty($url) && !$withoutHtmx) hx-get="{{ $url }}"
           hx-target="#tab__{{ $name }}"
           @if ($active && $shouldTrigger) hx-trigger="load" @endif
        @endif
        {{ $attributes }}
        >
        {!! $label !!}

        @if (isset($badge))
            <span class="tab-badge">{{ $badge }}</span>
        @endif
    </a>
</li>
