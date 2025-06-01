@if (user()->device()->isMobile())
    <nav class="tabbar" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true">
        <div class="tabbar__content">
            @foreach (navbar()->all() as $item)
                @if (count($item['children']) === 0)
                    <x-header.tabbar.link :item="$item" />
                @else
                    <x-header.tabbar.dropdown :item="$item" />
                @endif
            @endforeach
        </div>
    </nav>
@endif
