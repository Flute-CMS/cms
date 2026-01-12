<header class="flute_header" itemscope itemtype="https://schema.org/WPHeader">
    <nav class="navbar" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true" itemscope
        itemtype="https://schema.org/SiteNavigationElement">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="navbar__content">
                        <div class="navbar__left">
                            <div class="navbar__content-logo">
                                <a class="navbar__logo navbar__logo-dark" href="{{ url('/') }}"
                                    aria-label="{{ config('app.name') }} - Home" itemprop="url">
                                    <img src="{{ asset(config('app.logo')) }}" loading="lazy"
                                        alt="{{ config('app.name') }}" itemprop="logo">
                                </a>
                                <a class="navbar__logo navbar__logo-light" href="{{ url('/') }}"
                                    aria-label="{{ config('app.name') }} - Home" itemprop="url">
                                    <img src="{{ asset(config('app.logo_light', config('app.logo'))) }}" loading="lazy"
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

                            @if (!user()->device()->isMobile())
                                {{-- Navbar with morph dropdown --}}
                                <div class="navbar-dropdown" data-navbar-morph>
                                    <div class="navbar-dropdown__items">
                                        @foreach (navbar()->all() as $item)
                                            @if (count($item['children']) === 0)
                                                <a href="{{ url($item['url']) }}"
                                                    @if ($item['new_tab']) target="_blank" @endif
                                                    class="navbar-dropdown__item {{ active($item['url']) }}"
                                                    itemprop="url">
                                                    @if ($item['icon'])
                                                        <x-icon class="navbar-dropdown__item-icon"
                                                            path="{{ $item['icon'] }}" />
                                                    @endif
                                                    <span itemprop="name">{{ __($item['title']) }}</span>
                                                </a>
                                            @else
                                                <button class="navbar-dropdown__item navbar-dropdown__trigger"
                                                    data-morph-trigger="{{ $item['id'] }}">
                                                    @if ($item['icon'])
                                                        <x-icon class="navbar-dropdown__item-icon"
                                                            path="{{ $item['icon'] }}" />
                                                    @endif
                                                    <span>{{ __($item['title']) }}</span>
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
                                                                                        class="navbar-dropdown__menu-title">{{ __($child['title']) }}</span>
                                                                                    <div
                                                                                        class="navbar-dropdown__sublinks">
                                                                                        @foreach ($child['children'] as $subChild)
                                                                                            <a href="{{ url($subChild['url']) }}"
                                                                                                @if ($subChild['new_tab']) target="_blank" @endif
                                                                                                class="navbar-dropdown__sublink">
                                                                                                {{ __($subChild['title']) }}
                                                                                            </a>
                                                                                        @endforeach
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    @else
                                                                        {{-- Regular link without children --}}
                                                                        <a href="{{ url($child['url']) }}"
                                                                            @if ($child['new_tab']) target="_blank" @endif
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
                                                                                    class="navbar-dropdown__menu-title">{{ __($child['title']) }}</span>
                                                                                @if (!empty($child['description']))
                                                                                    <span
                                                                                        class="navbar-dropdown__menu-desc">{{ __($child['description']) }}</span>
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
                                    <x-header.notifications />

                                    @stack('navbar-actions')

                                    @if (isset($sections['navbar-actions']))
                                        {!! $sections['navbar-actions'] !!}
                                    @endif
                                @endif
                                <div class="navbar__separator"></div>
                                <x-header.profile />
                                <x-header.theme-switcher />
                            </ul>
                        @else
                            <ul class="navbar__actions login" aria-label="Authentication actions">
                                <x-header.language-selector />

                                @stack('navbar-actions-guest')

                                @if (isset($sections['navbar-actions-guest']))
                                    {!! $sections['navbar-actions-guest'] !!}
                                @endif

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
                                <div class="navbar__separator"></div>
                                <x-header.theme-switcher />
                            </ul>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </nav>

    @include('flute::partials.default-modals')
</header>
