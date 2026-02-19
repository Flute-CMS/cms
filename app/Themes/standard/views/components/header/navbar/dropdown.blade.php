@props(['item'])

<div class="navbar__items-item dropdown-item @if (!$item['icon']) without-icon @endif" data-dropdown-hover="true" data-dropdown-open="__dropdown_{{ $item['id'] }}">
    <span class="navbar__items-item-trigger">
        @if ($item['icon'])
            <x-icon class="navbar__items-item-icon" path="{{ $item['icon'] }}" />
        @endif
        @if (!empty($item['description']))
            <span class="navbar__items-item-content">
                <span>{{ __($item['title']) }}</span>
                <small class="navbar__items-item-description">{{ __($item['description']) }}</small>
            </span>
        @else
            {{ __($item['title']) }}
        @endif
        <x-icon class="navbar__items-item-icon-dropdown" path="ph.bold.caret-down-bold" />
    </span>
    @if (count($item['children']) > 0)
        <div class="navbar__dropdown" data-dropdown="__dropdown_{{ $item['id'] }}" hx-boost="true" hx-target="#main"
            hx-swap="outerHTML transition:true">
            @php
                $cols = count($item['children']) > 3 ? 2 : 1;
            @endphp
            <div class="navbar__dropdown-grid cols-{{ $cols }}">
                @foreach ($item['children'] as $child)
                    <div class="navbar__dropdown-item-wrapper">
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
                                <span class="navbar__dropdown-item-title" itemprop="name">{{ __($child['title']) }}</span>
                                @if (!empty($child['description']))
                                    <small class="navbar__dropdown-item-description">{{ __($child['description']) }}</small>
                                @endif
                            </span>
                        </a>
                        {{-- Sub-links displayed below the item like in React --}}
                        @if (!empty($child['children']) && count($child['children']) > 0)
                            <div class="navbar__dropdown-sublinks">
                                @foreach ($child['children'] as $lIdx => $subChild)
                                    <a href="{{ url($subChild['url']) }}" 
                                       @if ($subChild['new_tab']) target="_blank" rel="noopener" @endif
                                       class="navbar__dropdown-sublink">
                                        {{ __($subChild['title']) }}
                                    </a>
                                    @if ($lIdx < count($child['children']) - 1)
                                        <span class="navbar__dropdown-sublink-separator">•</span>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
