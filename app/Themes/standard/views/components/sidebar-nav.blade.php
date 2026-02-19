@php
    $isHidden = $attributes->get('data-hidden') === 'true';
@endphp
<aside class="sidebar-nav{{ $isHidden ? ' sidebar-nav--hidden' : '' }}" id="sidebar-nav" data-sidebar
    @if ($isHidden) aria-hidden="true" @endif hx-boost="true" hx-target="#main"
    hx-swap="outerHTML transition:true">
    <div class="sidebar-nav__header">
        <a class="sidebar-nav__logo" href="{{ url('/') }}" aria-label="{{ config('app.name') }} - Home">
            <img class="sidebar-nav__logo-img sidebar-nav__logo-img--dark" src="{{ asset(config('app.logo')) }}"
                alt="{{ config('app.name') }}">
            <img class="sidebar-nav__logo-img sidebar-nav__logo-img--light"
                src="{{ asset(config('app.logo_light', config('app.logo'))) }}" alt="{{ config('app.name') }}">
            <span class="sidebar-nav__logo-text">{{ config('app.name') }}</span>
        </a>
        <button type="button" class="sidebar-nav__contained-collapse" id="sidebar-contained-collapse"
            aria-label="{{ __('def.toggle_sidebar') }}">
            <x-icon path="ph.regular.sidebar-simple" />
        </button>
        <button type="button" class="sidebar-nav__mobile-close" id="sidebar-mobile-close"
            aria-label="{{ __('def.close') }}">
            <x-icon path="ph.regular.x" />
        </button>
    </div>

    <nav class="sidebar-nav__nav">
        @guest
            @if (config('auth.only_modal'))
                <a class="sidebar-nav__guest" href="#" data-modal-open="auth-modal"
                    data-tooltip="{{ __('def.login') }}" data-tooltip-placement="right" data-sidebar-tooltip>
                    <x-icon path="ph.regular.sign-in" class="sidebar-nav__guest-icon" />
                    <span class="sidebar-nav__guest-content">
                        <span class="sidebar-nav__guest-text">{{ __('def.not_logged_in') }}</span>
                        <span class="sidebar-nav__guest-link">{{ __('def.login') }}</span>
                    </span>
                </a>
            @else
                <a class="sidebar-nav__guest" href="{{ url('login') }}"
                    data-tooltip="{{ __('def.login') }}" data-tooltip-placement="right" data-sidebar-tooltip>
                    <x-icon path="ph.regular.sign-in" class="sidebar-nav__guest-icon" />
                    <span class="sidebar-nav__guest-content">
                        <span class="sidebar-nav__guest-text">{{ __('def.not_logged_in') }}</span>
                        <span class="sidebar-nav__guest-link">{{ __('def.login') }}</span>
                    </span>
                </a>
            @endif
        @endguest

        <div class="sidebar-nav__section">
            <div class="sidebar-nav__items">
                @foreach (navbar()->all() as $item)
                    @if (count($item['children']) === 0)
                        <a href="{{ url($item['url']) }}" @if ($item['new_tab']) target="_blank" rel="noopener" @endif
                            class="sidebar-nav__item {{ active($item['url']) }}">
                            @if ($item['icon'])
                                <span class="sidebar-nav__item-icon">
                                    <x-icon path="{{ $item['icon'] }}" />
                                </span>
                            @endif
                            <span class="sidebar-nav__item-text">{{ __($item['title']) }}</span>
                        </a>
                    @else
                        <div class="sidebar-nav__dropdown" data-sidebar-dropdown>
                            <button type="button" class="sidebar-nav__item" data-sidebar-dropdown-trigger>
                                @if ($item['icon'])
                                    <span class="sidebar-nav__item-icon">
                                        <x-icon path="{{ $item['icon'] }}" />
                                    </span>
                                @endif
                                <span class="sidebar-nav__item-text">{{ __($item['title']) }}</span>
                                <span class="sidebar-nav__item-chevron">
                                    <x-icon path="ph.bold.caret-down-bold" />
                                </span>
                            </button>

                            <div class="sidebar-nav__submenu" data-sidebar-submenu>
                                <div class="sidebar-nav__submenu-inner">
                                    @foreach ($item['children'] as $child)
                                        @if (!empty($child['children']) && count($child['children']) > 0)
                                            <div class="sidebar-nav__subgroup">
                                                <span
                                                    class="sidebar-nav__subgroup-title">{{ __($child['title']) }}</span>
                                                @foreach ($child['children'] as $subChild)
                                                    <a href="{{ url($subChild['url']) }}"
                                                        @if ($subChild['new_tab']) target="_blank" rel="noopener" @endif
                                                        class="sidebar-nav__subitem {{ active($subChild['url']) }}">
                                                        {{ __($subChild['title']) }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        @else
                                        <a href="{{ url($child['url']) }}"
                                            @if ($child['new_tab']) target="_blank" rel="noopener" @endif
                                            class="sidebar-nav__subitem {{ active($child['url']) }}">
                                                @if ($child['icon'])
                                                    <span class="sidebar-nav__subitem-icon">
                                                        <x-icon path="{{ $child['icon'] }}" />
                                                    </span>
                                                @endif
                                                {{ __($child['title']) }}
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            </div>

                            <div class="sidebar-nav__mini-dropdown">
                                <div class="sidebar-nav__mini-dropdown__title">{{ __($item['title']) }}</div>
                                @foreach ($item['children'] as $child)
                                    @if (!empty($child['children']) && count($child['children']) > 0)
                                        <div class="sidebar-nav__mini-dropdown__group">
                                            <div class="sidebar-nav__mini-dropdown__group-title">
                                                {{ __($child['title']) }}</div>
                                            @foreach ($child['children'] as $subChild)
                                                <a href="{{ url($subChild['url']) }}"
                                                    @if ($subChild['new_tab']) target="_blank" rel="noopener" @endif
                                                    class="sidebar-nav__mini-dropdown__item {{ active($subChild['url']) }}">
                                                    @if ($subChild['icon'])
                                                        <x-icon path="{{ $subChild['icon'] }}" />
                                                    @endif
                                                    {{ __($subChild['title']) }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @else
                                        <a href="{{ url($child['url']) }}"
                                            @if ($child['new_tab']) target="_blank" rel="noopener" @endif
                                            class="sidebar-nav__mini-dropdown__item {{ active($child['url']) }}">
                                            @if ($child['icon'])
                                                <x-icon path="{{ $child['icon'] }}" />
                                            @endif
                                            {{ __($child['title']) }}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        @stack('sidebar-nav')

        @if (isset($sections['sidebar-nav']))
            {!! $sections['sidebar-nav'] !!}
        @endif
    </nav>

    <div class="sidebar-nav__footer">
        @if (sizeof(social()->toDisplay()) > 0)
            <div class="sidebar-nav__socials">
                @foreach (social()->toDisplay() as $key => $icon)
                    <a href="{{ config("social.{$key}.url") }}" target="_blank" rel="noopener noreferrer"
                        aria-label="{{ $key }}" data-tooltip="{{ ucfirst($key) }}" data-tooltip-placement="top"
                        data-sidebar-tooltip>
                        <x-icon path="{{ $icon }}" />
                    </a>
                @endforeach
            </div>
        @endif

        <button type="button" class="sidebar-nav__toggle" id="sidebar-toggle"
            aria-label="{{ __('def.toggle_sidebar') }}" data-sidebar-tooltip>
            <x-icon path="ph.regular.sidebar-simple" />
            <span class="sidebar-nav__toggle-text">{{ __('def.toggle_sidebar') }}</span>
        </button>
    </div>
</aside>

<div class="sidebar-nav__overlay" id="sidebar-overlay"></div>
<div class="sidebar-hover-zone" id="sidebar-hover-zone"></div>
