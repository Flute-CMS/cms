<header class="flute_header" itemscope itemtype="https://schema.org/WPHeader">
    <nav class="navbar" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true" itemscope
        itemtype="https://schema.org/SiteNavigationElement">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="navbar__content">
                        <div class="navbar__content-logo">
                            <a class="navbar__logo navbar__logo-dark" href="{{ url('/') }}"
                                aria-label="{{ config('app.name') }} - Home" itemprop="url">
                                <img src="{{ asset(config('app.logo')) }}" loading="lazy" alt="{{ config('app.name') }}"
                                    itemprop="logo">
                            </a>
                            <a class="navbar__logo navbar__logo-light" href="{{ url('/') }}"
                                aria-label="{{ config('app.name') }} - Home" itemprop="url">
                                <img src="{{ asset(config('app.logo_light', config('app.logo'))) }}" loading="lazy"
                                    alt="{{ config('app.name') }}" itemprop="logo">
                            </a>
                            @if (!user()->device()->isMobile())
                                <x-header.socials />
                            @endif

                            @stack('navbar-logo')

                            @if (isset($sections['navbar-logo']))
                                {!! $sections['navbar-logo'] !!}
                            @endif
                        </div>

                        @if (!user()->device()->isMobile())
                            <div class="navbar__items" role="navigation" aria-label="Main navigation">
                                @foreach (navbar()->all() as $item)
                                    @if (count($item['children']) === 0)
                                        <x-header.navbar.link :item="$item" />
                                    @else
                                        <x-header.navbar.dropdown :item="$item" />
                                    @endif
                                @endforeach
                            </div>
                        @endif

                        @auth
                            <ul class="navbar__actions" aria-label="User actions">
                                <x-header.language-selector />
                                @if (!config('app.maintenance_mode') || (config('app.maintenance_mode') && is_debug()))
                                    <x-header.notifications />

                                    @stack('navbar-actions')

                                    @if (isset($sections['navbar-actions']))
                                        {!! $sections['navbar-actions'] !!}
                                    @endif
                                @endif

                                <x-header.profile />
                                <x-header.theme-switcher />
                            </ul>
                        @else
                            <ul class="navbar__actions login" aria-label="Authentication actions">
                                <x-header.language-selector />

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
                                            {{-- <x-icon path="{!! $icon !!}" /> --}}
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
