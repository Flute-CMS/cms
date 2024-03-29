<!-- Main Menu -->
@push('admin::main-sidebar')
    <div class="main-menu">
        <div class="title">@t('admin.menu.main-menu')</div>
        <div class="items">
            @foreach (admin()->sidebar()->main as $item)
                @if ((isset($item['permission']) && user()->hasPermission($item['permission'])) || !isset($item['permission']))
                    <a data-path="{{ $item['url'] }}" data-title="{{ $item['title'] }}"
                        class="item @if (request()->is($item['url'])) active @endif" href="{{ url($item['url']) }}">
                        <div class="name-icon">
                            <i class="ph {{ $item['icon'] }}"></i>
                            <p>{{ __($item['title']) }}</p>
                        </div>
                        @if (isset($item['tag']))
                            <div class="tag">{{ $item['tag'] }}</div>
                        @endif
                    </a>
                @endif
            @endforeach
        </div>
    </div>
@endpush
