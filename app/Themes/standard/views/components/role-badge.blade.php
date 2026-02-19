@props([
    'role',
    'mode' => 'auto',
    'size' => 'default',
])

@php
    $color = $role->color ?? '#8e8e8e';
    $hasIcon = !empty($role->icon);

    if ($mode === 'auto') {
        $mode = ($role->showIcon && $hasIcon) ? 'icon' : 'full';
    }
@endphp

@if ($mode === 'icon' && $hasIcon)
    <span {{ $attributes->merge(['class' => 'role-badge role-badge--icon role-badge--' . $size]) }}
          style="--role-color: {{ $color }}"
          data-tooltip="{{ $role->name }}">
        <x-icon path="{{ $role->icon }}" />
    </span>
@elseif ($mode === 'dot')
    <span {{ $attributes->merge(['class' => 'role-badge role-badge--dot']) }}
          style="--role-color: {{ $color }}"
          data-tooltip="{{ $role->name }}">
    </span>
@else
    <span {{ $attributes->merge(['class' => 'role-badge role-badge--full role-badge--' . $size]) }}
          style="--role-color: {{ $color }}">
        @if ($hasIcon)
            <x-icon path="{{ $role->icon }}" />
        @else
            <span class="role-badge__swatch"></span>
        @endif
        <span class="role-badge__name">{{ $role->name }}</span>
    </span>
@endif
