<aside @class([
    'sidebar',
    'collapsed' => cookie()->get('admin-sidebar-collapsed', 'false') === 'true' && !user()->device()->isMobile(),
]) hx-boost="true" hx-target="#main" hx-swap="morph:outerHTML transition:true">
    <div class="sidebar__header" hx-boost="false">
        <a href="{{ url('/') }}" class="sidebar__logo sidebar__logo-dark">
            <img src="{{ asset(config('app.logo')) }}" alt="{{ __('def.home') }}">
            <span>{{ config('app.name') }}</span>
        </a>
        <a href="{{ url('/') }}" class="sidebar__logo sidebar__logo-light">
            <img src="{{ asset(config('app.logo_light', config('app.logo'))) }}" alt="{{ __('def.home') }}">
            <span>{{ config('app.name') }}</span>
        </a>
        <button class="sidebar__toggle" data-tooltip="{{ __('def.toggle_sidebar') }}" data-tooltip-placement="right">
            <x-icon path="ph.regular.sidebar-simple" />
        </button>
        <button class="sidebar__toggle-mobile hamburger" aria-label="Toggle Sidebar">
            <x-icon path="ph.regular.x" />
        </button>
    </div>

    <div class="sidebar__search" hx-boost="false">
        <button class="sidebar__search-btn" id="sidebar-search-trigger">
            <x-icon path="ph.regular.magnifying-glass" class="sidebar__search-icon" />
            <span class="sidebar__search-text">{{ __('search.quick_search') }}</span>
            <kbd class="sidebar__search-kbd">Ctrl K</kbd>
        </button>
    </div>

    @php
        $factory = app(\Flute\Admin\AdminPackageFactory::class);
        $menuSections = $factory->getAllMenuItems();
        $moduleSections = $factory->getModuleMenuItems();
        $moduleUrlPrefixes = $factory->getModuleUrlPrefixes();
        $sidebarMode = cookie()->get('admin-sidebar-mode', 'nested');
        $isFlat = $sidebarMode === 'flat';

        usort($moduleSections, fn($a, $b) => strcasecmp($a['title'] ?? '', $b['title'] ?? ''));
        $moduleSections = array_values($moduleSections);
    @endphp

    <div class="sidebar__container {{ $isFlat ? 'sidebar__container--flat' : '' }}">
        @if ($isFlat)
            {{-- Flat mode: everything in one list (old behavior) --}}
            <nav class="sidebar__content">
                @foreach ($menuSections as $sectionIndex => $section)
                    @if (!empty($section['items']))
                        <div class="sidebar__section" data-section-id="{{ $sectionIndex }}"
                            data-section-key="{{ $section['_section_key'] ?? '' }}"
                        >
                            @if (!empty($section['title']))
                                <button class="sidebar__section-toggle" data-section-toggle="{{ $sectionIndex }}">
                                    <span class="sidebar__section-title">{{ $section['title'] }}</span>
                                    <x-icon path="ph.bold.caret-down-bold" class="sidebar__section-chevron" />
                                </button>
                            @endif
                            <div class="sidebar__menu">
                                <ul class="sidebar__menu-list" data-sidebar-sortable>
                                    @foreach ($section['items'] as $item)
                                        <x-menu-item :item="$item" />
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif
                @endforeach

                @foreach ($moduleSections as $modIndex => $modSection)
                    @if (!empty($modSection['items']))
                        <div class="sidebar__section" data-section-id="flat-mod-{{ $modIndex }}">
                            @if (!empty($modSection['title']))
                                <button class="sidebar__section-toggle" data-section-toggle="flat-mod-{{ $modIndex }}">
                                    <span class="sidebar__section-title">{{ $modSection['title'] }}</span>
                                    <x-icon path="ph.bold.caret-down-bold" class="sidebar__section-chevron" />
                                </button>
                            @endif
                            <div class="sidebar__menu">
                                <ul class="sidebar__menu-list">
                                    @foreach ($modSection['items'] as $item)
                                        <x-menu-item :item="$item" />
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif
                @endforeach
            </nav>
        @else
            {{-- Nested mode: multi-level sidebar --}}

            {{-- Level 1: Main navigation --}}
            <nav class="sidebar__content sidebar__level sidebar__level--main">
                @foreach ($menuSections as $sectionIndex => $section)
                    @if (!empty($section['items']))
                        <div class="sidebar__section" data-section-id="{{ $sectionIndex }}"
                            data-section-key="{{ $section['_section_key'] ?? '' }}"
                        >
                            @if (!empty($section['title']))
                                <button class="sidebar__section-toggle" data-section-toggle="{{ $sectionIndex }}">
                                    <span class="sidebar__section-title">{{ $section['title'] }}</span>
                                    <x-icon path="ph.bold.caret-down-bold" class="sidebar__section-chevron" />
                                </button>
                            @endif
                            <div class="sidebar__menu">
                                <ul class="sidebar__menu-list" data-sidebar-sortable>
                                    @foreach ($section['items'] as $item)
                                        <x-menu-item :item="$item" />
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif
                @endforeach

                @if (!empty($moduleSections))
                    <div class="sidebar__module-trigger-wrapper">
                        <button class="sidebar__module-trigger" data-sidebar-open-modules data-tooltip="{{ __('admin-menu.modules_nav') }}" data-tooltip-placement="right">
                            <span class="menu-icon">
                                <x-icon path="ph.regular.puzzle-piece" />
                            </span>
                            <span class="menu-title">{{ __('admin-menu.modules_nav') }}</span>
                            <span class="menu-badge">{{ count($moduleSections) }}</span>
                            <x-icon path="ph.regular.caret-right" class="menu-arrow" />
                        </button>
                    </div>
                @endif
            </nav>

            {{-- Level 2: Module directory --}}
            @if (!empty($moduleSections))
                <nav class="sidebar__content sidebar__level sidebar__level--modules">
                    <div class="sidebar__level-header">
                        <button class="sidebar__back-btn" data-sidebar-back data-tooltip-text="{{ __('admin-menu.back') }}" data-tooltip-placement="right">
                            <x-icon path="ph.regular.arrow-left" />
                        </button>
                        <span class="sidebar__level-title">
                            <x-icon path="ph.regular.puzzle-piece" />
                            {{ __('admin-menu.modules_nav') }}
                        </span>
                        <span class="sidebar__level-depth">
                            <span></span>
                            <span class="active"></span>
                            <span></span>
                        </span>
                    </div>

                    <div class="sidebar__section">
                        <div class="sidebar__menu">
                            <ul class="sidebar__menu-list">
                                @foreach ($moduleSections as $modIndex => $modSection)
                                    @if (!empty($modSection['items']))
                                        <li class="sidebar__menu-item">
                                            @if (count($modSection['items']) === 1)
                                                <a href="{{ $modSection['items'][0]['url'] ?? '#' }}" class="menu-item"
                                                    data-tooltip-text="{{ $modSection['title'] }}" data-tooltip-placement="right">
                                                    <span class="menu-icon">
                                                        <x-icon :path="$modSection['items'][0]['icon'] ?? 'ph.regular.puzzle-piece'" />
                                                    </span>
                                                    <span class="menu-title">{{ $modSection['title'] }}</span>
                                                </a>
                                            @else
                                                <button class="menu-item" data-sidebar-open-module="{{ $modIndex }}"
                                                    data-tooltip-text="{{ $modSection['title'] }}" data-tooltip-placement="right">
                                                    <span class="menu-icon">
                                                        <x-icon :path="$modSection['items'][0]['icon'] ?? 'ph.regular.puzzle-piece'" />
                                                    </span>
                                                    <span class="menu-title">{{ $modSection['title'] }}</span>
                                                    <span class="menu-badge">{{ count($modSection['items']) }}</span>
                                                    <x-icon path="ph.regular.caret-right" class="menu-arrow" />
                                                </button>
                                            @endif
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </nav>

                {{-- Level 3: Module detail pages --}}
                @foreach ($moduleSections as $modIndex => $modSection)
                    @if (count($modSection['items'] ?? []) > 1)
                        <nav class="sidebar__content sidebar__level sidebar__level--module-detail" data-module-level="{{ $modIndex }}">
                            <div class="sidebar__level-header">
                                <button class="sidebar__back-btn" data-sidebar-back-to-modules data-tooltip-text="{{ __('admin-menu.modules_nav') }}" data-tooltip-placement="right">
                                    <x-icon path="ph.regular.arrow-left" />
                                </button>
                                <span class="sidebar__level-title">
                                    <x-icon :path="$modSection['items'][0]['icon'] ?? 'ph.regular.puzzle-piece'" />
                                    {{ $modSection['title'] }}
                                </span>
                                <span class="sidebar__level-depth">
                                    <span></span>
                                    <span></span>
                                    <span class="active"></span>
                                </span>
                            </div>

                            <div class="sidebar__section">
                                <div class="sidebar__menu">
                                    <ul class="sidebar__menu-list">
                                        @foreach ($modSection['items'] as $item)
                                            <x-menu-item :item="$item" />
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </nav>
                    @endif
                @endforeach
            @endif
        @endif
    </div>

    @if (!$isFlat && !empty($moduleSections))
        <script type="application/json" id="sidebar-module-urls">
            @json($moduleUrlPrefixes)
        </script>
        <script type="application/json" id="sidebar-big-module-urls">
            @json(collect($moduleSections)->filter(fn($m) => count($m['items'] ?? []) > 1)->mapWithKeys(function ($mod, $idx) {
                return [$idx => collect($mod['items'])->pluck('url')->filter()->map(fn($u) => parse_url($u, PHP_URL_PATH))->values()];
            }))
        </script>
    @endif

    @if (!empty($moduleSections))
        <div class="sidebar__footer" hx-boost="false">
            <button class="sidebar__mode-toggle" data-sidebar-mode-toggle
                data-tooltip="{{ $isFlat ? __('admin-menu.mode_nested') : __('admin-menu.mode_flat') }}"
                data-tooltip-placement="right">
                <x-icon :path="$isFlat ? 'ph.regular.stack' : 'ph.regular.rows'" />
                <span class="sidebar__mode-label">{{ $isFlat ? __('admin-menu.mode_nested') : __('admin-menu.mode_flat') }}</span>
            </button>
        </div>
    @endif
</aside>
