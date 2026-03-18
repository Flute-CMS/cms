<li class="navbar__profile-wrapper">
    <button class="navbar__profile-trigger" data-profile-toggle
        aria-label="{{ __('def.profile') }} {{ user()->name }}"
        aria-expanded="false" aria-haspopup="true">
        <img data-profile-avatar src="{{ url(user()->avatar) }}" alt="{{ user()->name }}" width="32"
            height="32">
    </button>

    <div class="profile-dropdown" data-profile-dropdown aria-hidden="true" hx-boost="false">
        {{-- Hero: Banner + Avatar + Name --}}
        <div class="profile-dropdown__hero" @if(user()->banner) style="--banner: url('{{ url(user()->banner) }}')" @endif>
            <div class="profile-dropdown__hero-avatar">
                <img src="{{ url(user()->avatar) }}" alt="{{ user()->name }}" loading="lazy">
            </div>
            <span class="profile-dropdown__hero-name">{!! user()->getCurrentUser()->getDisplayName() !!}</span>
            @if (user()->login)
                <span class="profile-dropdown__hero-sub">{{ '@' . user()->login }}</span>
            @elseif (user()->email)
                <span class="profile-dropdown__hero-sub">{{ user()->email }}</span>
            @endif
        </div>

        {{-- Balance --}}
        @if (config('app.balance_enabled', true) && sizeof(payments()->getAllGateways() ?? 0 > 0))
            <div class="profile-dropdown__section">
                @if (config('lk.only_modal'))
                    <button class="profile-dropdown__balance" data-modal-open="lk-modal">
                        <span class="profile-dropdown__balance-icon">
                            <x-icon path="ph.regular.wallet" />
                        </span>
                        <span class="profile-dropdown__balance-label">{{ __('def.balance') }}</span>
                        <span class="profile-dropdown__balance-amount">{{ number_format(user()->balance, 2) }} {{ config('lk.currency_view') }}</span>
                        <span class="profile-dropdown__balance-topup">
                            <x-icon path="ph.bold.plus-bold" />
                        </span>
                    </button>
                @else
                    <a href="{{ url('/lk') }}" class="profile-dropdown__balance" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true">
                        <span class="profile-dropdown__balance-icon">
                            <x-icon path="ph.regular.wallet" />
                        </span>
                        <span class="profile-dropdown__balance-label">{{ __('def.balance') }}</span>
                        <span class="profile-dropdown__balance-amount">{{ number_format(user()->balance, 2) }} {{ config('lk.currency_view') }}</span>
                        <span class="profile-dropdown__balance-topup">
                            <x-icon path="ph.bold.plus-bold" />
                        </span>
                    </a>
                @endif
            </div>
        @endif

        {{-- Menu Grid --}}
        <div class="profile-dropdown__menu" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true">
            @if (config('app.profile_enabled', true))
                <a href="{{ url('profile/' . user()->getUrl()) }}" class="profile-dropdown__menu-item">
                    <span class="profile-dropdown__menu-icon">
                        <x-icon path="ph.regular.user-circle" />
                    </span>
                    <span class="profile-dropdown__menu-label">@t('def.my_profile')</span>
                </a>
            @endif

            @can('admin')
                <a href="{{ url('admin') }}" class="profile-dropdown__menu-item" hx-boost="false">
                    <span class="profile-dropdown__menu-icon">
                        <x-icon path="ph.regular.shield-checkered" />
                    </span>
                    <span class="profile-dropdown__menu-label">@t('def.admin_panel')</span>
                </a>
            @endcan

            {{-- Extension point: modules push items here as profile-dropdown__menu-item --}}
            @stack('profile-dropdown')
        </div>

        {{-- Footer: Settings + Theme + Logout --}}
        <div class="profile-dropdown__footer">
            @if (config('app.profile_enabled', true))
                <a href="{{ url('profile/settings') }}" class="profile-dropdown__footer-settings" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true">
                    <x-icon path="ph.regular.gear" />
                    <span>@t('def.settings')</span>
                </a>
            @endif

            <div class="profile-dropdown__footer-actions">
                {{-- Stack for extra footer buttons --}}
                @stack('profile-dropdown-footer')

                @if(config('app.change_theme'))
                    <button class="profile-dropdown__footer-btn" data-tooltip="@t('def.change_theme')" data-tooltip-placement="top" onclick="document.querySelector('#theme-toggle')?.click()">
                        <x-icon path="ph.regular.sun" class="sun-icon" @style(['display: none' => cookie()->get('theme', 'dark') === 'dark']) />
                        <x-icon path="ph.regular.moon" class="moon-icon" @style(['display: none' => cookie()->get('theme', 'dark') === 'light']) />
                    </button>
                @endif

                <form action="{{ url('logout') }}" method="POST" hx-boost="false">
                    @csrf
                    <button type="submit" class="profile-dropdown__footer-btn profile-dropdown__footer-btn--danger" data-tooltip="@t('def.logout')" data-tooltip-placement="top">
                        <x-icon path="ph.regular.sign-out" />
                    </button>
                </form>
            </div>
        </div>
    </div>
</li>
