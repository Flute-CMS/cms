@props(['children', 'level' => 0])

@foreach ($children as $child)
    @if (!empty($child['children']) && count($child['children']) > 0)
        <div class="tabbar__modal-submenu" data-level="{{ $level }}">
            <button type="button" class="tabbar__modal-item tabbar__modal-submenu-trigger">
                @if ($child['icon'])
                    <x-icon path="{{ $child['icon'] }}" />
                @endif
                @if (!empty($child['description']))
                    <div class="tabbar__modal-item-content">
                        <span>{{ __($child['title']) }}</span>
                        <small class="tabbar__modal-item-description">{{ __($child['description']) }}</small>
                    </div>
                @else
                    <span>{{ __($child['title']) }}</span>
                @endif
                <x-icon class="tabbar__modal-submenu-arrow" path="ph.bold.caret-down-bold" />
            </button>
            <div class="tabbar__modal-submenu-content" style="padding-left: {{ ($level + 1) * 16 }}px;">
                <x-header.tabbar.dropdown-children :children="$child['children']" :level="$level + 1" />
            </div>
        </div>
    @else
        <a href="{{ url($child['url']) }}" @if ($child['new_tab']) target="_blank" rel="noopener" @endif
            class="tabbar__modal-item" itemprop="url" style="@if($level > 0) padding-left: {{ $level * 16 }}px; @endif">
            @if ($child['icon'])
                <x-icon path="{{ $child['icon'] }}" />
            @endif
            @if (!empty($child['description']))
                <div class="tabbar__modal-item-content">
                    <span itemprop="name">{{ __($child['title']) }}</span>
                    <small class="tabbar__modal-item-description">{{ __($child['description']) }}</small>
                </div>
            @else
                <span itemprop="name">{{ __($child['title']) }}</span>
            @endif
        </a>
    @endif
@endforeach
