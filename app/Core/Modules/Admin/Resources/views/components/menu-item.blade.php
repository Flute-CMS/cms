@props(['item'])

@if (isset($item['type']) && $item['type'] === 'header')
    <li class="sidebar__menu-header">{{ __($item['title']) }}</li>
@else
    <li class="sidebar__menu-item @if (! empty($item['children'])) sub-menu @endif">
        <a href="{{ $item['url'] ?? '#' }}" class="menu-item">
            <span class="menu-icon">
                <x-icon :path="$item['icon'] ?? 'ph.regular.circle'" />
            </span>
            <span class="menu-title">{{ __($item['title']) }}</span>
            @if (! empty($item['badge']))
                <span class="badge {{ $item['badge-type'] ?? 'primary' }}">{{ $item['badge'] }}</span>
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