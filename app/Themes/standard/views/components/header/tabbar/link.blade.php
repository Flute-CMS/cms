@props(['item'])

@if ($item['icon'])
    <a href="{{ url($item['url']) }}" @if ($item['new_tab']) target="_blank" @endif
        class="tabbar__item {{ active($item['url']) }}" itemprop="url">
        <x-icon path="{{ $item['icon'] }}" />

        <p itemprop="name">
            {{ __($item['title']) }}
        </p>
    </a>
@endif
