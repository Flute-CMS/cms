@props(['item'])

<a href="{{ url($item['url']) }}" @if ($item['new_tab']) target="_blank" @endif
    class="navbar__items-item {{ active($item['url']) }} @if(! $item['icon']) without-icon @endif" itemprop="url"
    {{-- Я думал разумным оставить здесь `preload`, но мне показалось что он делает только хуже. --}}>
    @if ($item['icon'])
        <x-icon path="{{ $item['icon'] }}" />
    @endif
    <span itemprop="name">{{ __($item['title']) }}</span>
</a>