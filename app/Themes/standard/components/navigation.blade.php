@if ($nav_type === 'default')
    @push('footer')
        @at(tt('assets/js/navigation.js'))
    @endpush

    @php
        $hasAdditional = false;
    @endphp

    <div class="navigation">
        @if (sizeof(navbar()->all()) > 0)
            <div class="additional-menu opened">
                <div class="menu-item close-item">
                    <i class="ph ph-x"></i>
                </div>

                <div class="additional">
                    @foreach (navbar()->all() as $item)
                        @if (isset($item['children']) && count($item['children']) > 0)
                            @php
                                $hasAdditional = true;
                            @endphp
                            <div class="add-panel">
                                <p>
                                    {{ __($item['title']) }}
                                </p>

                                @if (isset($item['children']) && count($item['children']) > 0)
                                    <ul class="submenu">
                                        @foreach ($item['children'] as $child)
                                            <li>
                                                <a href="{{ url($child['url']) }}"
                                                    @if ($child['new_tab']) target="_blank" @endif>
                                                    @if ($child['icon'])
                                                        <i class="{{ $child['icon'] }}"></i>
                                                    @endif
                                                    {{ __($child['title']) }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        <div class="menu-items">
            @if ($hasAdditional)
                <div class="menu-item first-item">
                    <i class="ph ph-list"></i>
                </div>
            @endif

            @if (sizeof(navbar()->all()) > 0)
                <div class="other-items">
                    @foreach (navbar()->all() as $item)
                        @if (isset($item['children']) && count($item['children']) === 0)
                            <a data-tooltip='{{ __($item['title']) }}' data-tooltip-conf='right'
                                href="{{ url($item['url']) }}" @if ($item['new_tab']) target="_blank" @endif
                                class="menu-item @if (!count($item['children']) && request()->is($item['url'])) active @endif">
                                @if ($item['icon'])
                                    <i class="{{ $item['icon'] }}"></i>
                                @endif
                            </a>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endif
