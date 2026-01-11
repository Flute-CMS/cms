@props(['item'])

@if ($item['icon'])
    <button class="tabbar__item" data-modal-open="tabbar-{{ $item['id'] }}">
        <x-icon path="{{ $item['icon'] }}" />

        <p>
            {{ __($item['title']) }}
        </p>
    </button>

    @push('footer')
        <x-modal id="tabbar-{{ $item['id'] }}" title="{{ __($item['title']) }}">
            <div class="tabbar__modal-items" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true">
                <x-header.tabbar.dropdown-children :children="$item['children']" :level="0" />
            </div>
        </x-modal>
    @endpush
@endif
