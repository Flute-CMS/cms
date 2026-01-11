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
                    <div class="navbar__dropdown-item-title">{{ __($child['title']) }}</div>
                    @if (!empty($child['description']))
                        <div class="navbar__dropdown-item-description">{{ __($child['description']) }}</div>
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
           @if ($child['new_tab']) target="_blank" @endif
           class="navbar__dropdown-item" 
           itemprop="url">
            @if ($child['icon'])
                <div class="navbar__dropdown-item-icon">
                    <x-icon path="{{ $child['icon'] }}" />
                </div>
            @endif
            <div class="navbar__dropdown-item-content">
                <div class="navbar__dropdown-item-title" itemprop="name">{{ __($child['title']) }}</div>
                @if (!empty($child['description']))
                    <div class="navbar__dropdown-item-description">{{ __($child['description']) }}</div>
                @endif
            </div>
        </a>
    @endif
@endforeach
