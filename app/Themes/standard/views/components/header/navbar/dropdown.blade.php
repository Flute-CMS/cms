@props(['item'])

<div class="navbar__items-item dropdown-item @if (!$item['icon']) without-icon @endif">
    <p data-dropdown-open="__dropdown_{{ $item['id'] }}">
        @if ($item['icon'])
            <x-icon path="{{ $item['icon'] }}" />
        @endif
        {{ __($item['title']) }}
        <x-icon path="ph.bold.caret-down-bold" />
    </p>
    @if (count($item['children']) > 0)
        <div class="navbar__dropdown" data-dropdown="__dropdown_{{ $item['id'] }}" hx-boost="true" hx-target="#main"
            hx-swap="outerHTML transition:true">
            @foreach ($item['children'] as $child)
                <a href="{{ url($child['url']) }}" @if ($child['new_tab']) target="_blank" @endif itemprop="url">
                    @if ($child['icon'])
                        <x-icon path="{{ $child['icon'] }}" />
                    @endif
                    <span itemprop="name">{{ __($child['title']) }}</span>
                </a>
            @endforeach
        </div>
    @endif
</div>
