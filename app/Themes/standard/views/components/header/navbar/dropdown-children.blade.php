@props(['children'])

@foreach ($children as $child)
    @if (!empty($child['children']) && count($child['children']) > 0)
        <div class="navbar__dropdown-submenu">
            <div class="navbar__dropdown-submenu-trigger">
                @if ($child['icon'])
                    <div class="navbar__dropdown-item-icon">
                        <x-icon path="{{ $child['icon'] }}" />
                    </div>
                @endif
                <div class="navbar__dropdown-item-content">
                    <div class="navbar__dropdown-item-title">{{ transValue($child['title']) }}</div>
                    @if (!empty($child['description']))
                        <div class="navbar__dropdown-item-description">{{ transValue($child['description']) }}</div>
                    @endif
                </div>
                <x-icon class="navbar__dropdown-submenu-arrow" path="ph.bold.caret-right-bold" />
            </div>
            <div class="navbar__dropdown-submenu-content">
                <x-header.navbar.dropdown-children :children="$child['children']" />
            </div>
        </div>
    @else
        <a href="{{ url($child['url']) }}" 
           @if ($child['new_tab']) target="_blank" rel="noopener" @endif
           class="navbar__dropdown-item" 
           itemprop="url">
            @if ($child['icon'])
                <span class="navbar__dropdown-item-icon">
                    <x-icon path="{{ $child['icon'] }}" />
                </span>
            @endif
            <span class="navbar__dropdown-item-content">
                <span class="navbar__dropdown-item-title" itemprop="name">{{ transValue($child['title']) }}</span>
                @if (!empty($child['description']))
                    <span class="navbar__dropdown-item-description">{{ transValue($child['description']) }}</span>
                @endif
            </span>
        </a>
    @endif
@endforeach
