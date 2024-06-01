@push('admin::sidebar')
    <div class="sidebar-menu">
        @foreach (admin()->sidebar()->categories() as $sectionName)
            <div class="menu-section">
                <div class="title">
                    @if ($sectionName === __($sectionName))
                        @t('admin.menu.' . $sectionName)
                    @else
                        @t($sectionName)
                    @endif
                </div>
                <div class="items">
                    @foreach (admin()->sidebar()->{$sectionName} as $item)
                        @if (empty($item['items']))
                            @php
                                $isActiveItem = request()->is($item['url'] ?? '');
                            @endphp

                            @if ((isset($item['permission']) && user()->hasPermission($item['permission'])) || !isset($item['permission']))
                                <button class="item {{ $isActiveItem ? 'opened active' : '' }}">
                                    <a @if (isset($item['url'])) href="{{ $item['url'] }}" @endif
                                        class="head-button">
                                        <div class="name-icon">
                                            <i class="ph {{ $item['icon'] }}"></i>
                                            <p>{{ __($item['title']) }}</p>
                                        </div>
                                    </a>
                                </button>
                            @endif
                        @else
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
                                <button class="item {{ $isActiveItem ? 'opened active' : '' }}">
                                    <a @if (isset($item['url'])) href="{{ $item['url'] }}" @endif
                                        class="head-button">
                                        <div class="name-icon">
                                            <i class="ph {{ $item['icon'] }}"></i>
                                            <p>{{ __($item['title']) }}</p>
                                        </div>
                                        <i class="ph ph-caret-down"></i>
                                    </a>

                                    <div class="btn-add-menu">
                                        @foreach ($item['items'] as $subItem)
                                            @if ((isset($subItem['permission']) && user()->hasPermission($subItem['permission'])) || !isset($subItem['permission']))
                                                <a href="{{ $subItem['url'] }}"
                                                    class="submenu-item {{ request()->is($subItem['url']) ? 'active' : '' }}">
                                                    {{ __($subItem['title']) }}
                                                </a>
                                            @endif
                                        @endforeach
                                    </div>
                                </button>
                            @endif
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endpush
