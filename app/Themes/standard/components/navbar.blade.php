@push('footer')
    <script>
        const NOTIFICATIONS_MODE = `{{ config('app.notifications') }}`;
    </script>
    @at(tt('assets/js/navbar.js'))
@endpush

@auth
    @push('miniprofile_buttons')
        <a href="{{ url('profile/edit') }}" class="miniprofile_button">
            <i class="ph ph-pencil"></i>
            <p>@t('profile.edit_profile')</p>
        </a>
        @can('admin')
            <a href="{{ url('admin/') }}" class="miniprofile_button">
                <i class="ph ph-gear"></i>
                <p>@t('def.admin_panel')</p>
            </a>
        @endcan
    @endpush

    @push('nav_right_controls')
        <li class="notifications_li">
            <a id="openNotifications">
                <i class="ph ph-bell"></i>
            </a>
            <div class="notifications_container">
                <div class="notifications_header">
                    <div class="notifications_header_title">
                        {{ t('def.notifications') }}
                    </div>
                    <div class="notifications_header_clear">
                        <button>@t('def.clear')</button>
                    </div>
                </div>
                <div class="notifications_body">
                </div>
            </div>
        </li>
        <li>
            <a id="openSearch">
                <i class="ph ph-magnifying-glass"></i>
            </a>
        </li>
        <li>
            <a id="openMiniProfile">
                <i class="ph-bold ph-caret-down"></i>
                <img class="mini_avatar" loading="lazy" src="{{ url(user()->avatar) }}" alt="">
            </a>
            <div class="miniprofile_container">
                <div class="miniprofile_base">
                    <div class="miniprofile_header">
                        <a href="{{ url('profile/' . user()->getUrl()) }}" class="mp-flex">
                            <div class="miniprofile_name">
                                <div class="miniprofile_name_text">
                                    {{ user()->name }}
                                </div>
                                <div class="miniprofile_my_profile">
                                    @t('def.my_profile')
                                </div>
                            </div>
                            <i class="ph-bold ph-arrow-up-right"></i>
                        </a>
                    </div>
                    <div class="miniprofile_actions">
                        <a href="{{ url('/lk') }}" class="miniprofile_balance">
                            <div class="miniprofile_balance_title">
                                @t('def.my_balance')
                            </div>
                            <div class="miniprofile_balance_text" id="user_balance">
                                {{ user()->balance }} {{ app()->get('lk.currency_view') }}
                            </div>
                        </a>
                        <div class="miniprofile_lang">
                            <img src="{{ url('assets/img/langs/' . app()->getLang() . '.svg') }}" alt="">
                        </div>
                        <a href="{{ url('logout') }}" class="miniprofile_logout">
                            <i class="ph ph-sign-out"></i>
                        </a>
                    </div>
                    <div class="miniprofile_body">
                        <div class="miniprofile_buttons">
                            @stack('miniprofile_buttons')
                        </div>
                    </div>
                </div>
                <div class="miniprofile_langs_container" style="display: none">
                    @foreach (app('lang.available') as $lang)
                        <a href="{{ url(null, ['lang' => $lang]) }}"
                            data-lang="{{ $lang }}"
                            class="miniprofile_langs_item @if ($lang === app()->getLang()) active @endif">
                            <img src="{{ url('assets/img/langs/' . $lang . '.svg') }}" alt="">
                            <p>@t('langs.' . $lang)</p>
                        </a>
                    @endforeach
                </div>
            </div>
        </li>
    @endpush
    @elseauth
    @push('nav_right_controls_guest')
        <li>
            <div class="nav_guest_lang">
                <img src="{{ url('assets/img/langs/' . app()->getLang() . '.svg') }}" alt="">
            </div>
            <div class="miniprofile_container">
                <div class="miniprofile_langs_container">
                    @foreach (app('lang.available') as $lang)
                        <a href="{{ url(null, ['lang' => $lang]) }}" 
                            data-lang="{{ $lang }}"
                            data-skip="1"
                            class="miniprofile_langs_item @if ($lang === app()->getLang()) active @endif">
                            <img src="{{ url('assets/img/langs/' . $lang . '.svg') }}" alt="">
                            <p>@t('langs.' . $lang)</p>
                        </a>
                    @endforeach
                </div>
            </div>
        </li>
    @endpush
@endauth

<nav class="navbar container">
    <div class="row" id="nav_desc">
        <div class="col-md-12">
            <div class="navbar--container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    <img src="@asset(app('app.logo'))" loading="lazy" alt="logo">
                    <h2 class="navbar-brand-name">
                        {{ app('app.name') }}
                    </h2>
                </a>

                @if ($nav_type === 'navbar')
                    <div class="navbar--container-items">
                        @if (sizeof(navbar()->all()) > 0)
                            @foreach (navbar()->all() as $item)
                                @if (sizeof($item['children']) === 0)
                                    <a href="{{ url($item['url']) }}"
                                        @if ($item['new_tab']) target="_blank" @endif
                                        class="menu-item @if (!sizeof($item['children']) && request()->is($item['url'])) active @endif">
                                        @if ($item['icon'])
                                            <i class="{{ $item['icon'] }}"></i>
                                        @endif
                                        @if ($item['title'])
                                            <p>{{ __($item['title']) }}</p>
                                        @endif
                                    </a>
                                @elseif(sizeof($item['children']) > 0)
                                    <div class="menu-item">
                                        @if ($item['icon'])
                                            <i class="{{ $item['icon'] }}"></i>
                                        @endif
                                        <p>
                                            {{ __($item['title']) }}
                                            <i class="ph ph-caret-down"></i>
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
                        @endif
                    </div>
                @endif

                <div class="navbar-panel">
                    @can('admin.pages')
                        @if (!page()->isEditorDisabled())
                            <label id="editMode">
                                <input type="checkbox" @if (request()->input('editMode')) checked @endif>
                                <span class="toggle_background">
                                    <div class="circle-icon"></div>
                                    <div class="vertical_line"></div>
                                </span>
                            </label>
                        @endif
                    @endcan

                    @auth
                        <ul class="navbar_right_controls">
                            @stack('nav_right_controls')
                        </ul>
                        @elseauth
                        <div class="default-panel">
                            <ul class="navbar_right_controls controls_not_auth">
                                @stack('nav_right_controls_guest')
                            </ul>
                            <div class="buttons">
                                <a class="btn ghosted size-m" role="button" href="{{ url('login') }}">
                                    @t('def.auth')
                                </a>
                                <a href="{{ url('register') }}" class="btn btn--with-icon size-m outline">
                                    @t('def.register')
                                    <span class="btn__icon arrow"><i class="ph ph-arrow-right"></i></span>
                                </a>
                            </div>
                        </div>
                    @endauth
                </div>
            </div>
            <div class="navbar-search">
                <div class="input">
                    <i class="ph-bold ph-magnifying-glass"></i>
                    <input type="text" id="search" placeholder="@t('def.lets_search')">
                </div>
                <div class="right-search">
                    <i class="ph-bold ph-x" id="closeSearch"></i>
                </div>
                <div id="search_container"></div>
            </div>
        </div>
    </div>
    <div class="row" id="nav_mobile">
        <div class="col-md-12">
            <div class="navbar--container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    <img src="@asset(app('app.logo'))" loading="lazy" alt="logo">
                    <h2 class="navbar-brand-name">
                        {{ app('app.name') }}
                    </h2>
                </a>
                <div class="mobile_menu navbar-burger">
                    <i class="ph ph-list"></i>
                </div>
            </div>
        </div>
    </div>
</nav>
<div class="navbar_mobile">
    <ul class="navbar_mobile_icons">
        @auth
            <li>
                <a class="profile_in_navbar_mobile" href="{{ url('profile/' . user()->id) }}">
                    <img loading="lazy" src="{{ url(user()->avatar) }}" alt="">
                    <div>
                        <h2>{{ user()->name }}</h2>
                        <p>@t('def.my_profile')</p>
                    </div>
                </a>
            </li>
        @endauth
        @foreach (navbar()->all() as $item)
            <li @if (isset($item['children']) && count($item['children']) > 0) data-child="1" @endif>

                <a @if (!isset($item['children']) || (isset($item['children']) && count($item['children']) == 0)) href="{{ url($item['url']) }}" @if ($item['new_tab']) target="_blank" @endif
                    @endif
                    @if (request()->is($item['url'])) class="active" @endif>
                    @if ($item['icon'])
                        <i class="{{ $item['icon'] }}"></i>
                    @endif
                    <div class="item_text">{{ __($item['title']) }}</div>
                </a>

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
            </li>
        @endforeach
    </ul>
    @auth
        <a class="logout" href="{{ url('logout') }}" role="button">@t('def.logout')</a>
    @endauth

    @guest
        <a class="login" href="{{ url('login') }}" role="button">@t('def.login')</a>
    @endguest
</div>
