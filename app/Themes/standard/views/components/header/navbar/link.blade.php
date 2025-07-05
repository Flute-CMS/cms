@props(['item'])

<a href="{{ url($item['url']) }}" @if ($item['new_tab']) target="_blank" @endif
    class="navbar__items-item {{ active($item['url']) }} @if(! $item['icon']) without-icon @endif" itemprop="url"
    {{-- Я думал разумным оставить здесь `preload`, но мне показалось что он делает только хуже. --}}>
    @if ($item['icon'])
        <x-icon path="{{ $item['icon'] }}" />
    @endif
    @if (!empty($item['description']))
        <div class="navbar__items-item-content">
            <span itemprop="name">{{ __($item['title']) }}</span>
            <small class="navbar__items-item-description">{{ __($item['description']) }}</small>
        </div>
    @else
        <span itemprop="name">{{ __($item['title']) }}</span>
    @endif
</a>