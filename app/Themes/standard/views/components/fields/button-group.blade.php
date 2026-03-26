@props([
    'name' => '',
    'options' => [],
    'value' => null,
    'icons' => false,
])

<div class="button-group" data-name="{{ $name }}">
    @foreach ($options as $key => $option)
        @php
            $isActive = $value == $key;
            $label = is_array($option) ? ($option['label'] ?? $key) : $option;
            $icon = is_array($option) ? ($option['icon'] ?? null) : null;
        @endphp
        <button type="button" 
            class="button-group__item {{ $isActive ? 'active' : '' }}" 
            data-value="{{ $key }}"
            @if(isset($option['title'])) title="{{ $option['title'] }}" @endif>
            @if ($icon)
                <x-icon :path="$icon" />
            @endif
            @if (!$icons || !$icon)
                <span>{{ $label }}</span>
            @endif
        </button>
    @endforeach
    <input type="hidden" name="{{ $name }}" id="{{ $name }}" value="{{ $value }}" />
</div>

