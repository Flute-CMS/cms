<aside class="sidebar" id="sidebar-nav" itemscope itemtype="https://schema.org/SiteNavigationElement">
    {{-- Brand / Logo Area --}}
    <div class="sidebar__brand">
        <a class="sidebar__brand-logo sidebar__brand-logo--dark" href="{{ url('/') }}" itemprop="url">
            <img src="{{ asset(config('app.logo')) }}" alt="{{ config('app.name') }}" itemprop="logo">
        </a>
        <a class="sidebar__brand-logo sidebar__brand-logo--light" href="{{ url('/') }}" itemprop="url">
            <img src="{{ asset(config('app.logo_light', config('app.logo'))) }}" alt="{{ config('app.name') }}" itemprop="logo">
        </a>
    </div>

    {{-- Navigation --}}
    <nav class="sidebar__nav" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true">
        {{-- Section Label --}}
        <div class="sidebar__section-label">{{ __('def.menu') }}</div>

        {{-- Stack from modules --}}
        @stack('navbar-logo')
        @if (isset($sections['navbar-logo']))
            {!! $sections['navbar-logo'] !!}
        @endif

        {{-- Menu Items --}}
        <ul class="sidebar__menu">
            @foreach (navbar()->all() as $item)
                @if (count($item['children']) === 0)
                    {{-- Simple link --}}
                    <li class="sidebar__item">
                        <a href="{{ url($item['url']) }}"
                            @if ($item['new_tab']) target="_blank" @endif
                            class="sidebar__link {{ active($item['url']) ? 'sidebar__link--active' : '' }}"
                            itemprop="url">
                            {{-- Active indicator --}}
                            @if (active($item['url']))
                                <span class="sidebar__active-indicator"></span>
                            @endif
                            @if ($item['icon'])
                                <span class="sidebar__icon">
                                    <x-icon path="{{ $item['icon'] }}" />
                                </span>
                            @endif
                            <span class="sidebar__text" itemprop="name">{{ __($item['title']) }}</span>
                        </a>
                    </li>
                @else
                    {{-- Dropdown --}}
                    <li class="sidebar__item sidebar__item--has-children {{ collect($item['children'])->contains(fn($c) => active($c['url'])) ? 'is-open' : '' }}">
                        <button type="button" class="sidebar__link sidebar__link--trigger" data-sidebar-dropdown>
                            @if ($item['icon'])
                                <span class="sidebar__icon">
                                    <x-icon path="{{ $item['icon'] }}" />
                                </span>
                            @endif
                            <span class="sidebar__text">{{ __($item['title']) }}</span>
                            <span class="sidebar__chevron">
                                <x-icon path="ph.bold.caret-down-bold" />
                            </span>
                        </button>
                        <ul class="sidebar__submenu">
                            @foreach ($item['children'] as $child)
                                <li class="sidebar__subitem">
                                    <a href="{{ url($child['url']) }}"
                                        @if ($child['new_tab']) target="_blank" @endif
                                        class="sidebar__sublink {{ active($child['url']) ? 'sidebar__sublink--active' : '' }}"
                                        itemprop="url">
                                        <span class="sidebar__dot"></span>
                                        <span class="sidebar__subtext" itemprop="name">{{ __($child['title']) }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endif
            @endforeach
        </ul>

        {{-- Socials --}}
        @if (!empty(footer()->socials()->all()))
            <div class="sidebar__section-label">{{ __('def.socials') }}</div>
            <div class="sidebar__socials">
                @foreach (footer()->socials()->all() as $social)
                    <a href="{{ $social->url }}" data-tooltip="@t($social->name)" data-tooltip-placement="right" 
                        aria-label="@t($social->name)" target="_blank" rel="noopener">
                        <x-icon path="{!! $social->icon !!}" />
                    </a>
                @endforeach
            </div>
        @endif
    </nav>

    {{-- Footer / User Area --}}
    <div class="sidebar__footer">
        {{-- Stack for modules --}}
        @auth
            @if (!config('app.maintenance_mode') || (config('app.maintenance_mode') && user()->can('admin.pages')))
                @stack('navbar-actions')
                @if (isset($sections['navbar-actions']))
                    {!! $sections['navbar-actions'] !!}
                @endif
            @endif
        @else
            @stack('navbar-actions-guest')
            @if (isset($sections['navbar-actions-guest']))
                {!! $sections['navbar-actions-guest'] !!}
            @endif
        @endauth

        {{-- User Profile / Auth --}}
        @auth
            <div class="sidebar__user">
                <button hx-get="{{ url('sidebar/miniprofile') }}" hx-target="#right-sidebar-content" hx-boost="false"
                    class="sidebar__user-btn" hx-swap="transition:false">
                    <div class="sidebar__user-avatar">
                        <img data-profile-avatar src="{{ url(user()->avatar) }}" alt="{{ user()->name }}">
                        <span class="sidebar__user-status"></span>
                    </div>
                    <div class="sidebar__user-info">
                        <span class="sidebar__user-name">{{ user()->name }}</span>
                        <span class="sidebar__user-role">{{ user()->getMainRole() ?? __('def.user') }}</span>
                    </div>
                </button>
            </div>
        @else
            <div class="sidebar__auth">
                @if (!config('auth.only_social', false) || (config('auth.only_social') && social()->isEmpty()))
                    @if (config('auth.only_modal'))
                        <button type="button" class="sidebar__auth-btn" data-modal-open="auth-modal">
                            <x-icon path="ph.regular.sign-in" />
                            <span>@t('def.login')</span>
                        </button>
                    @else
                        <a href="{{ url('login') }}" class="sidebar__auth-btn">
                            <x-icon path="ph.regular.sign-in" />
                            <span>@t('def.login')</span>
                        </a>
                    @endif
                @endif
            </div>
        @endauth
    </div>
</aside>
