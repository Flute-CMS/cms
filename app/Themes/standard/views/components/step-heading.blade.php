@props([
    'label' => null,
    'active' => false,
    'completed' => false,
    'disabled' => false,
    'name' => 'step',
    'url' => null,
    'icon' => null,
    'description' => null,
])

@php
    $name = preg_replace('/[^\w-]/', '_', $name);
    $active = filter_var($active, FILTER_VALIDATE_BOOLEAN);
    $completed = filter_var($completed, FILTER_VALIDATE_BOOLEAN);
    $disabled = filter_var($disabled, FILTER_VALIDATE_BOOLEAN);

    $stateClass = $completed ? 'completed' : ($active ? 'active' : '');
    if ($disabled) $stateClass .= ' is-disabled';

    $label = $label ?? $slot;
@endphp

<li class="step-item {{ $stateClass }}"
    role="listitem"
    data-step-heading="{{ $name }}"
    data-step-id="steps__{{ $name }}">
    <button type="button"
        class="step-item__trigger"
        aria-current="{{ $active ? 'step' : 'false' }}"
        @if ($url) data-step-url="{{ $url }}" @endif
        @if ($disabled) disabled aria-disabled="true" @endif
        {{ $attributes }}>
        <span class="step-item__dot" aria-hidden="true">
            {{-- Check icon always in DOM, visibility controlled by CSS via .completed --}}
            <x-icon path="ph.bold.check-bold" class="step-item__check" />
            @if ($icon)
                <x-icon :path="$icon" class="step-item__icon" />
            @endif
        </span>
        <span class="step-item__body">
            @if ($description)
                <span class="step-item__subtitle">{!! $description !!}</span>
            @endif
            <span class="step-item__label">{!! $label !!}</span>
        </span>
    </button>
    <span class="step-item__connector" aria-hidden="true">
        <span class="step-item__connector-fill"></span>
    </span>
</li>
