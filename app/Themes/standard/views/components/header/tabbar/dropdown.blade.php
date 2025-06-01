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
                @foreach ($item['children'] as $child)
                    <a href="{{ url($child['url']) }}" @if ($child['new_tab']) target="_blank" @endif
                        class="tabbar__modal-item" itemprop="url">
                        @if ($child['icon'])
                            <x-icon path="{{ $child['icon'] }}" />
                        @endif
                        <span itemprop="name">{{ __($child['title']) }}</span>
                    </a>
                @endforeach
            </div>
        </x-modal>
    @endpush
@endif
