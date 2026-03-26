@props(['item'])

@php
    $title = $item['title'] ?? '';
    $itemKey = $item['key'] ?? ($item['_config_key'] ?? '');
@endphp

@if (isset($item['type']) && $item['type'] === 'header')
    <li class="sidebar__menu-header">{{ $title }}</li>
@else
    <li class="sidebar__menu-item @if (! empty($item['children'])) sub-menu @endif"
        @if ($itemKey) data-item-key="{{ $itemKey }}" @endif>
        <a href="{{ $item['url'] ?? '#' }}" class="menu-item" data-tooltip="{{ $title }}" data-tooltip-placement="right">
            <span class="menu-icon">
                <x-icon :path="$item['icon'] ?? 'ph.regular.circle'" />
            </span>
            <span class="menu-title">{{ $title }}</span>
            @if (! empty($item['badge']))
                <span class="menu-badge menu-badge--{{ $item['badge-type'] ?? '' }}">{{ $item['badge'] }}</span>
            @endif
            @if (! empty($item['children']))
                <x-icon path="ph.regular.caret-right" class="menu-arrow" />
            @endif
        </a>
        @if (! empty($item['children']))
            <div class="menu-sub">
                <ul hx-boost="true" hx-target="#main" hx-swap="morph:outerHTML transition:true">
                    @foreach ($item['children'] as $child)
                        <x-menu-item :item="$child" />
                    @endforeach
                </ul>
            </div>
        @endif
    </li>
@endif
