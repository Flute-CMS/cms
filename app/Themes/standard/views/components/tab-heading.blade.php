@props([
    'label' => null,
    'active' => false,
    'disabled' => false,
    'name' => 'tab',
    'url' => null,
    'badge' => null,
    'withoutHtmx' => false,
    'reloadable' => false,
])

@php
    $name = preg_replace('/[^\w-]/', '_', $name);
    $active = filter_var($active, FILTER_VALIDATE_BOOLEAN);
    $disabled = filter_var($disabled, FILTER_VALIDATE_BOOLEAN);
    $reloadable = filter_var($reloadable, FILTER_VALIDATE_BOOLEAN);

    $label = $label ?? $slot;
@endphp

<li class="tab-item @if ($active) active @endif @if ($disabled) is-disabled @endif"
    role="presentation" data-tab-heading="{{ $name }}">
    <a role="tab" id="tab-{{ $name }}" aria-selected="{{ $active ? 'true' : 'false' }}"
        aria-controls="tab__{{ $name }}" data-tab-id="tab__{{ $name }}"
        data-reloadable="{{ $reloadable ? 'true' : 'false' }}"
        @if ($withoutHtmx && $url) href="{{ $url }}" @endif
        @if (!empty($url) && !$withoutHtmx) 
            hx-get="{{ $url }}"
            hx-target="#tab__{{ $name }}"
            hx-swap="innerHTML" 
            @if ($active && !$reloadable)
                hx-trigger="load" 
            @elseif ($reloadable)
                hx-trigger="@if ($active) load, @endif click"
            @else
                hx-trigger="click once"
            @endif
            hx-indicator="#tab__{{ $name }}"
        @endif
        tabindex="{{ $active ? '0' : '-1' }}"
        @if ($disabled) aria-disabled="true" @endif
        {{ $attributes }}
        >
        {!! $label !!}

        @if (isset($badge))
            <span class="tab-badge" aria-hidden="true">{{ $badge }}</span>
        @endif
    </a>
</li>
