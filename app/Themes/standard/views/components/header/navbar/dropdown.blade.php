@props(['item'])

<div class="navbar__items-item dropdown-item @if (!$item['icon']) without-icon @endif">
    <p data-dropdown-open="__dropdown_{{ $item['id'] }}">
        @if ($item['icon'])
            <x-icon class="navbar__items-item-icon" path="{{ $item['icon'] }}" />
        @endif
        @if (!empty($item['description']))
            <div class="navbar__items-item-content">
                <span>{{ __($item['title']) }}</span>
                <small class="navbar__items-item-description">{{ __($item['description']) }}</small>
            </div>
        @else
            {{ __($item['title']) }}
        @endif
        <x-icon class="navbar__items-item-icon-dropdown" path="ph.bold.caret-down-bold" />
    </p>
    @if (count($item['children']) > 0)
        <div class="navbar__dropdown" data-dropdown="__dropdown_{{ $item['id'] }}" hx-boost="true" hx-target="#main"
            hx-swap="outerHTML transition:true">
            @foreach ($item['children'] as $child)
                <a href="{{ url($child['url']) }}" @if ($child['new_tab']) target="_blank" @endif
                    itemprop="url" @if (!empty($child['description'])) class="navbar__dropdown-item-with-description" @endif>
                    @if ($child['icon'])
                        <x-icon class="navbar__items-item-icon" path="{{ $child['icon'] }}" />
                    @endif
                    @if (!empty($child['description']))
                        <div class="navbar__dropdown-item-content">
                            <span itemprop="name">{{ __($child['title']) }}</span>
                            <small class="navbar__dropdown-item-description">{{ __($child['description']) }}</small>
                        </div>
                    @else
                        <span itemprop="name">{{ __($child['title']) }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    @endif
</div>
