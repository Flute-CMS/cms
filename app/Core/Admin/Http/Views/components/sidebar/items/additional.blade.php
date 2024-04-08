@push('admin::additional-sidebar')
    <div class="additional-menu">
        <div class="title">@t('admin.menu.additional-menu')</div>
        <div class="items">
            @foreach (admin()->sidebar()->additional as $item)
                @if (empty($item['items']))
                    @php
                        $isActiveItem = request()->is($item['url'] ?? '');
                    @endphp

                    @if ((isset($item['permission']) && user()->hasPermission($item['permission'])) || !isset($item['permission']))
                        <button class="item {{ $isActiveItem ? 'opened active' : '' }}">
                            <a @if (isset($item['url'])) href="{{ $item['url'] }}" @endif class="head-button">
                                <div class="name-icon">
                                    <i class="ph {{ $item['icon'] }}"></i>
                                    <p>{{ __($item['title']) }}</p>
                                </div>
                            </a>
                        </button>
                    @endif
                @endif
            @endforeach

            @foreach (admin()->sidebar()->additional as $item)
                @if (!empty($item['items']))
                    @php
                        $isActiveItem = false;
                        foreach ($item['items'] as $subItem) {
                            if (request()->is($subItem['url'])) {
                                $isActiveItem = true;
                                break;
                            }
                        }
                        if (isset($item['url']) && request()->is($item['url'])) {
                            $isActiveItem = true;
                        }
                    @endphp

                    @if ((isset($item['permission']) && user()->hasPermission($item['permission'])) || !isset($item['permission']))
                        @if (!isset($item['list']))
                            <button class="item {{ $isActiveItem ? 'opened active' : '' }}">
                                <a @if (isset($item['url'])) href="{{ $item['url'] }}" @endif class="head-button">
                                    <div class="name-icon">
                                        <i class="ph {{ $item['icon'] }}"></i>
                                        <p>{{ __($item['title']) }}</p>
                                    </div>

                                    @if (!isset($item['url']))
                                        <i class="ph-bold ph-caret-down"></i>
                                    @endif
                                </a>

                                @if (isset($item['items']))
                                    <div class="btn-add-menu">
                                        @foreach ($item['items'] as $subItem)
                                            @php
                                                $isActiveSubItem = request()->is($subItem['url']);
                                            @endphp
                                            @if ((isset($subItem['permission']) && user()->hasPermission($subItem['permission'])) || !isset($subItem['permission']))
                                                <a data-path="{{ $subItem['url'] }}" data-title="{{ $subItem['title'] }}"
                                                    href="{{ url($subItem['url']) }}"
                                                    class="{{ $isActiveSubItem ? 'active' : '' }}">{{ __($subItem['title']) }}</a>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </button>
                        @else
                            <button class="item {{ $isActiveItem ? 'opened active' : '' }}">
                                @if (isset($item['items']))
                                    <a @if (isset($item['url'])) href="{{ $item['url'] }}" @endif class="title">
                                        <i class="ph {{ $item['icon'] }}"></i>
                                        <p>{{ __($item['title']) }}</p>
                                    </a>
                                @else
                                    <a @if (isset($item['url'])) href="{{ $item['url'] }}" @endif
                                        class="head-button">
                                        <div class="name-icon">
                                            <i class="ph {{ $item['icon'] }}"></i>
                                            <p>{{ __($item['title']) }}</p>
                                        </div>
                                    </a>
                                @endif

                                @if (isset($item['items']))
                                    <div class="items-additional">
                                        @foreach ($item['items'] as $subItem)
                                            @php
                                                $isActiveSubItem = request()->is($subItem['url']);
                                            @endphp
                                            @if ((isset($subItem['permission']) && user()->hasPermission($subItem['permission'])) || !isset($subItem['permission']))
                                                <a data-path="{{ $subItem['url'] }}" data-title="{{ $subItem['title'] }}"
                                                    href="{{ url($subItem['url']) }}"
                                                    class="{{ $isActiveSubItem ? 'active' : '' }}">
                                                    @if (isset($subItem['icon']))
                                                        <i class="ph {{ $item['icon'] }}"></i>
                                                    @endif
                                                    {{ __($subItem['title']) }}
                                                </a>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </button>
                        @endif
                    @endif
                @endif
            @endforeach
        </div>
    </div>
@endpush
