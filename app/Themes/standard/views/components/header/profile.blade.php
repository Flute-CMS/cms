<li class="navbar__profile-wrapper">
    <button class="navbar__profile-trigger" data-profile-toggle
        aria-label="{{ __('def.profile') }} {{ user()->name }}"
        aria-expanded="false" aria-haspopup="true">
        <img data-profile-avatar src="{{ url(user()->avatar) }}" alt="{{ user()->name }}" loading="lazy" width="32"
            height="32">
    </button>

    <div class="profile-dropdown" data-profile-dropdown aria-hidden="true" hx-boost="false">
        {{-- User Info --}}
        <div class="profile-dropdown__user">
            <div class="profile-dropdown__avatar">
                <img src="{{ url(user()->avatar) }}" alt="{{ user()->name }}" loading="lazy">
            </div>
            <div class="profile-dropdown__info">
                <span class="profile-dropdown__name">{{ user()->name }}</span>
                <span class="profile-dropdown__meta">#{{ user()->id }}</span>
            </div>
        </div>

        {{-- Balance --}}
        @if (config('app.balance_enabled', true) && sizeof(payments()->getAllGateways() ?? 0 > 0))
            <div class="profile-dropdown__section">
                <div class="profile-dropdown__balance">
                    <div class="profile-dropdown__balance-left">
                        <x-icon path="ph.regular.wallet" />
                        <span>{{ __('def.balance') }}</span>
                    </div>
                    <div class="profile-dropdown__balance-right">
                        <span class="profile-dropdown__balance-amount">{{ number_format(user()->balance, 2) }} {{ config('lk.currency_view') }}</span>
                        @if (config('lk.only_modal'))
                            <button class="profile-dropdown__balance-btn" data-modal-open="lk-modal" data-tooltip="@t('def.top_up')">
                                <x-icon path="ph.bold.plus-bold" />
                            </button>
                        @else
                            <a href="{{ url('/lk') }}" class="profile-dropdown__balance-btn" hx-boost="true" hx-target="#main"
                                hx-swap="outerHTML transition:true" data-tooltip="@t('def.top_up')">
                                <x-icon path="ph.bold.plus-bold" />
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- Menu Items --}}
        <div class="profile-dropdown__section" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true">
            @if (config('app.profile_enabled', true))
                <a href="{{ url('profile/' . user()->getUrl()) }}" class="profile-dropdown__item">
                    <x-icon path="ph.regular.user" />
                    <span>@t('def.my_profile')</span>
                </a>
                <a href="{{ url('profile/settings') }}" class="profile-dropdown__item">
                    <x-icon path="ph.regular.gear" />
                    <span>@t('def.settings')</span>
                </a>
            @endif
            @can('admin')
                <a href="{{ url('admin') }}" class="profile-dropdown__item" hx-boost="false">
                    <x-icon path="ph.regular.shield-check" />
                    <span>@t('def.admin_panel')</span>
                </a>
            @endcan
        </div>

        {{-- Stack for additional items --}}
        @stack('profile-dropdown')

        {{-- Logout --}}
        <div class="profile-dropdown__section profile-dropdown__section--footer">
            <form action="{{ url('logout') }}" method="POST" hx-boost="false">
                @csrf
                <button type="submit" class="profile-dropdown__item profile-dropdown__item--danger">
                    <x-icon path="ph.regular.sign-out" />
                    <span>@t('def.logout')</span>
                </button>
            </form>
        </div>
    </div>
</li>
