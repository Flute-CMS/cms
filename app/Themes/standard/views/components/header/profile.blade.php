<li class="navbar__profile-wrapper">
    <button class="navbar__profile-trigger" data-profile-toggle
        aria-label="{{ __('def.profile') }} {{ user()->name }}"
        aria-expanded="false" aria-haspopup="true">
        <img data-profile-avatar src="{{ url(user()->avatar) }}" alt="{{ user()->name }}" width="32"
            height="32">
    </button>

    <div class="profile-dropdown" data-profile-dropdown aria-hidden="true" hx-boost="false">
        {{-- Hero Card --}}
        <div class="profile-dropdown__hero" @if(user()->banner) style="--banner: url('{{ url(user()->banner) }}')" @endif>
            <div class="profile-dropdown__identity">
                <img class="profile-dropdown__avatar" src="{{ url(user()->avatar) }}" alt="{{ user()->name }}" loading="lazy">
                <div class="profile-dropdown__info">
                    <span class="profile-dropdown__name">{{ user()->name }}</span>
                    @if (user()->login)
                        <span class="profile-dropdown__sub">{{ '@' . user()->login }}</span>
                    @elseif (user()->email)
                        <span class="profile-dropdown__sub">{{ user()->email }}</span>
                    @endif
                </div>
            </div>
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

        {{-- Quick Actions Grid --}}
        <div class="profile-dropdown__actions" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true">
            @if (config('app.profile_enabled', true))
                <a href="{{ url('profile/' . user()->getUrl()) }}" class="profile-dropdown__action">
                    <span class="profile-dropdown__action-icon">
                        <x-icon path="ph.regular.user" />
                    </span>
                    <span class="profile-dropdown__action-label">@t('def.my_profile')</span>
                </a>
                <a href="{{ url('profile/settings') }}" class="profile-dropdown__action">
                    <span class="profile-dropdown__action-icon">
                        <x-icon path="ph.regular.gear" />
                    </span>
                    <span class="profile-dropdown__action-label">@t('def.settings')</span>
                </a>
            @endif
            @can('admin')
                <a href="{{ url('admin') }}" class="profile-dropdown__action" hx-boost="false">
                    <span class="profile-dropdown__action-icon">
                        <x-icon path="ph.regular.shield-check" />
                    </span>
                    <span class="profile-dropdown__action-label">@t('def.admin_panel')</span>
                </a>
            @endcan
        </div>

        {{-- Stack for additional items --}}
        @stack('profile-dropdown')

        {{-- Logout --}}
        <div class="profile-dropdown__footer">
            <form action="{{ url('logout') }}" method="POST" hx-boost="false">
                @csrf
                <button type="submit" class="profile-dropdown__logout">
                    <x-icon path="ph.regular.sign-out" />
                    <span>@t('def.logout')</span>
                </button>
            </form>
        </div>
    </div>
</li>
