@props(['item'])

<a href="{{ url($item['url']) }}" @if ($item['new_tab']) target="_blank" @endif
    class="tabbar__item {{ active($item['url']) }}" itemprop="url">
    <x-icon path="{{ $item['icon'] }}" />

    <p itemprop="name">
        {{ __($item['title']) }}
        @if (!empty($item['description']))
            <small class="tabbar__item-description" style="display: block; font-size: 0.75em; opacity: 0.7; margin-top: 2px;">
                {{ __($item['description']) }}
            </small>
        @endif
    </p>
</a>
