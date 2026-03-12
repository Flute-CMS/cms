@php
    $_currentThemeMode = config('app.change_theme', true)
        ? cookie()->get('theme', config('app.default_theme', 'dark'))
        : config('app.default_theme', 'dark');
    $_themeColors = app('flute.view.manager')->getColors($_currentThemeMode);
    $_navStyle = $_themeColors['--nav-style'] ?? 'default';
    $_isSidebarMode = $_navStyle === 'sidebar';
@endphp
<header class="flute_header" itemscope itemtype="https://schema.org/WPHeader">
    <nav class="navbar" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true" itemscope
        itemtype="https://schema.org/SiteNavigationElement">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="navbar__content">
                        <div class="navbar__left">
                            @if ($_isSidebarMode)
                                {{-- Mobile toggle for sidebar --}}
                                <button type="button" class="navbar__mobile-toggle" id="mobile-sidebar-toggle"
                                    aria-label="{{ __('def.menu') }}">
                                    <x-icon path="ph.regular.list" />
                                </button>

                                {{-- Breadcrumb in navbar for sidebar mode --}}
                                <nav class="breadcrumb breadcrumb--navbar" id="navbar-breadcrumb" aria-label="Breadcrumb navigation"
                                    hx-swap-oob="true" hx-boost="true" hx-target="#main"
                                    hx-swap="outerHTML transition:true">
                                    @if (breadcrumb()->all())
                                        <ul class="breadcrumb-links">
                                            @foreach (breadcrumb()->all() as $index => $crumb)
                                                <li>
                                                    @if ($index > 0)
                                                        <div class="breadcrumb-box">
                                                            <svg class="breadcrumb-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                                <path fill-rule="evenodd"
                                                                    d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z"
                                                                    clip-rule="evenodd" />
                                                            </svg>
                                                            @if ($crumb['url'] && $index < count(breadcrumb()->all()) - 1)
                                                                <a href="{{ $crumb['url'] }}" class="breadcrumb-text">{{ $crumb['title'] }}</a>
                                                            @else
                                                                <span class="breadcrumb-text">{{ $crumb['title'] }}</span>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <a href="{{ $crumb['url'] ?: '#' }}" class="breadcrumb-box">
                                                            <span class="breadcrumb-text">{{ $crumb['title'] }}</span>
                                                        </a>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </nav>

                                @stack('navbar-logo')

                                @if (isset($sections['navbar-logo']))
                                    {!! $sections['navbar-logo'] !!}
                                @endif
                            @else
                                <div class="navbar__content-logo">
                                    <a class="navbar__logo navbar__logo-dark" href="{{ url('/') }}"
                                        aria-label="{{ config('app.name') }} - Home" itemprop="url">
                                    <img src="{{ asset(config('app.logo')) }}"
                                        alt="{{ config('app.name') }}" itemprop="logo">
                                    </a>
                                    <a class="navbar__logo navbar__logo-light" href="{{ url('/') }}"
                                        aria-label="{{ config('app.name') }} - Home" itemprop="url">
                                    <img src="{{ asset(config('app.logo_light', config('app.logo'))) }}"
                                        alt="{{ config('app.name') }}" itemprop="logo">
                                    </a>
                                </div>
                                <div class="navbar__separator"></div>
                                @if (!user()->device()->isMobile())
                                    <x-header.socials />
                                @endif

                                @stack('navbar-logo')

                                @if (isset($sections['navbar-logo']))
                                    {!! $sections['navbar-logo'] !!}
                                @endif
                                <div class="navbar__separator"></div>
                            @endif

                            @if (!user()->device()->isMobile())
                                <div class="navbar-dropdown" data-navbar-morph>
                                    <div class="navbar-dropdown__items">
                                        @foreach (navbar()->all() as $item)
                                            @if (count($item['children']) === 0)
                                                <a href="{{ url($item['url']) }}"
                                                    @if ($item['new_tab']) target="_blank" rel="noopener" @endif
                                                    class="navbar-dropdown__item {{ active($item['url']) }}"
                                                    itemprop="url">
                                                    @if ($item['icon'])
                                                        <x-icon class="navbar-dropdown__item-icon"
                                                            path="{{ $item['icon'] }}" />
                                                    @endif
                                                    <span itemprop="name">{{ transValue($item['title']) }}</span>
                                                </a>
                                            @else
                                                <button class="navbar-dropdown__item navbar-dropdown__trigger"
                                                    data-morph-trigger="{{ $item['id'] }}">
                                                    @if ($item['icon'])
                                                        <x-icon class="navbar-dropdown__item-icon"
                                                            path="{{ $item['icon'] }}" />
                                                    @endif
                                                    <span>{{ transValue($item['title']) }}</span>
                                                    <x-icon class="navbar-dropdown__chevron"
                                                        path="ph.bold.caret-down-bold" />
                                                </button>
                                            @endif
                                        @endforeach
                                    </div>

                                    {{-- Morphing dropdown container --}}
                                    <div class="navbar-dropdown__popup" data-morph-dropdown>
                                        <div class="navbar-dropdown__box" data-morph-box>
                                            @foreach (navbar()->all() as $item)
                                                @if (count($item['children']) > 0)
                                                    <div class="navbar-dropdown__content"
                                                        data-morph-content="{{ $item['id'] }}" hx-boost="true"
                                                        hx-target="#main" hx-swap="outerHTML transition:true">
                                                        @php
                                                            $cols = count($item['children']) > 3 ? 2 : 1;
                                                        @endphp
                                                        <div class="navbar-dropdown__grid cols-{{ $cols }}">
                                                            @foreach ($item['children'] as $child)
                                                                @php
                                                                    $hasSubChildren =
                                                                        !empty($child['children']) &&
                                                                        count($child['children']) > 0;
                                                                @endphp
                                                                <div class="navbar-dropdown__menu-item group">
                                                                    @if ($hasSubChildren)
                                                                        {{-- If has 3rd level children, use div container --}}
                                                                        <div class="navbar-dropdown__menu-group">
                                                                            <div class="navbar-dropdown__menu-header">
                                                                                @if ($child['icon'])
                                                                                    <span
                                                                                        class="navbar-dropdown__menu-icon">
                                                                                        <x-icon
                                                                                            path="{{ $child['icon'] }}" />
                                                                                    </span>
                                                                                @endif

                                                                                <div
                                                                                    class="navbar-dropdown__menu-group-content">
                                                                                    <span
                                                                                        class="navbar-dropdown__menu-title">{{ transValue($child['title']) }}</span>
                                                                                    <div
                                                                                        class="navbar-dropdown__sublinks">
                                                                                        @foreach ($child['children'] as $subChild)
                                                                                            <a href="{{ url($subChild['url']) }}"
                                                                                                @if ($subChild['new_tab']) target="_blank" rel="noopener" @endif
                                                                                                class="navbar-dropdown__sublink">
                                                                                                {{ transValue($subChild['title']) }}
                                                                                            </a>
                                                                                        @endforeach
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    @else
                                                                        {{-- Regular link without children --}}
                                                                        <a href="{{ url($child['url']) }}"
                                                                            @if ($child['new_tab']) target="_blank" rel="noopener" @endif
                                                                            class="navbar-dropdown__menu-link">
                                                                            @if ($child['icon'])
                                                                                <span
                                                                                    class="navbar-dropdown__menu-icon">
                                                                                    <x-icon
                                                                                        path="{{ $child['icon'] }}" />
                                                                                </span>
                                                                            @endif
                                                                            <span class="navbar-dropdown__menu-text">
                                                                                <span
                                                                                    class="navbar-dropdown__menu-title">{{ transValue($child['title']) }}</span>
                                                                                @if (!empty($child['description']))
                                                                                    <span
                                                                                        class="navbar-dropdown__menu-desc">{{ transValue($child['description']) }}</span>
                                                                                @endif
                                                                            </span>
                                                                        </a>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        @auth
                            <ul class="navbar__actions" aria-label="User actions">
                                <x-header.language-selector />
                                @if (!config('app.maintenance_mode') || (config('app.maintenance_mode') && user()->can('admin.pages')))
                                    @stack('navbar-actions')

                                    @if (isset($sections['navbar-actions']))
                                        {!! $sections['navbar-actions'] !!}
                                    @endif
                                @endif
                                <div class="navbar__separator"></div>
                                <x-header.theme-switcher />
                                @if (config('app.notifications_enabled', true) && (!config('app.maintenance_mode') || (config('app.maintenance_mode') && user()->can('admin.pages'))))
                                    <x-header.notifications />
                                @endif
                                <x-header.profile />
                            </ul>
                        @else
                            <ul class="navbar__actions login" aria-label="Authentication actions">
                                <x-header.language-selector />

                                @stack('navbar-actions-guest')

                                @if (isset($sections['navbar-actions-guest']))
                                    {!! $sections['navbar-actions-guest'] !!}
                                @endif

                                @if (config('app.auth_enabled', true))
                                    <div class="navbar__separator"></div>

                                    @if (!config('auth.only_social', false) || (config('auth.only_social') && social()->isEmpty()))
                                        @if (config('auth.only_modal'))
                                            <li>
                                                <x-link class="navbar__actions-login link" data-modal-open="auth-modal">
                                                    @t('def.login')
                                                </x-link>
                                            </li>
                                            @if (!config('app.maintenance_mode'))
                                                <li>
                                                    <x-button data-modal-open="register-modal" size="tiny">
                                                        @t('def.register')
                                                    </x-button>
                                                </li>
                                            @endif
                                        @else
                                            <li>
                                                <x-link class="navbar__actions-login link" href="{{ url('login') }}">
                                                    @t('def.login')
                                                </x-link>
                                            </li>
                                            @if (!config('app.maintenance_mode'))
                                                <li>
                                                    <x-button href="{{ url('register') }}" size="tiny">
                                                        @t('def.register')
                                                    </x-button>
                                                </li>
                                            @endif
                                        @endif
                                    @endif

                                    @if (config('auth.only_social', false) && sizeof(social()->getAll()) === 1)
                                        @php
                                            $item = social()->toDisplay();
                                            $key = key($item);
                                            $icon = $item[$key];
                                        @endphp

                                        <li>
                                            <x-button href="{{ url('social/' . $key) }}" size="tiny" hx-boost="false">
                                                @t('auth.social.auth_via', [':social' => $key])
                                            </x-button>
                                        </li>
                                    @elseif(config('auth.only_social', false) && sizeof(social()->getAll()) > 1)
                                        <li>
                                            @if (config('auth.only_modal'))
                                                <x-button class="navbar__actions-login link" size="tiny"
                                                    data-modal-open="auth-modal">
                                                    @t('def.login')
                                                </x-button>
                                            @else
                                                <x-button href="{{ url('login') }}" size="tiny">
                                                    @t('def.login')
                                                </x-button>
                                            @endif
                                        </li>
                                    @endif
                                @endif
                                <div class="navbar__separator"></div>
                                <x-header.theme-switcher />
                            </ul>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </nav>
</header>
