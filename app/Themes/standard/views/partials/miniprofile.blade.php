<div class="right_sidebar__container miniprofile__container" id="right-sidebar-content" role="dialog" aria-modal="true"
    data-a11y-dialog-ignore-focus-trap>
    <div class="miniprofile__main">
        <header class="right_sidebar__header">
            <h5 class="right_sidebar__title" id="modal-1-title">
                @t('def.my_profile')
            </h5>
            <button class="right_sidebar__close" aria-label="Close modal" data-a11y-dialog-hide="right-sidebar"
                data-original-tabindex="null"></button>
        </header>
        <div class="right_sidebar__content miniprofile__content">
            <div class="miniprofile__user">
                <img src="{{ url(user()->avatar) }}" alt="{{ user()->name }}" class="miniprofile__avatar"
                    loading="lazy">
                <div class="miniprofile__user-content">
                    <h6>{{ user()->name }}</h6>
                    <p>#{{ user()->id }}</p>
                </div>

                @if (sizeof(payments()->getAllGateways() ?? 0 > 0))
                    <div class="miniprofile__balance">
                        <div class="miniprofile__balance-content">
                            <small>{{ __('def.balance') }}</small>
                            <h6>{{ user()->balance }} {{ config('lk.currency_view') }}</h6>
                        </div>

                        @if (config('lk.only_modal'))
                            <x-button size="tiny" type="outline-accent" class="miniprofile__balance-btn"
                                data-modal-open="lk-modal">
                                {{ __('def.top_up') }}
                            </x-button>
                        @else
                            <x-button size="tiny" type="outline-accent" class="miniprofile__balance-btn"
                                hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true"
                                href="{{ url('/lk') }}">
                                {{ __('def.top_up') }}
                            </x-button>
                        @endif
                    </div>
                @endif
            </div>

            <div class="miniprofile__content-buttons" hx-boost="true" hx-target="#main"
                hx-swap="outerHTML transition:true">
                <a href="{{ url('profile/' . user()->getUrl()) }}" class="miniprofile__button">
                    <x-icon path="ph.bold.user-bold" />
                    @t('def.profile')
                </a>
                @can('admin')
                    <a href="{{ url('admin') }}" class="miniprofile__button" hx-boost="false">
                        <x-icon path="ph.bold.gear-bold" />
                        @t('def.admin_panel')
                    </a>
                @endcan
                <a href="{{ url('profile/settings') }}" class="miniprofile__button">
                    <x-icon path="ph.bold.pencil-bold" />
                    @t('profile.edit.title')
                </a>
            </div>
        </div>
    </div>
    <div class="right_sidebar__footer w-100">
        <x-button type="error" class="w-100" href="{{ url('logout') }}" isLink="true">
            @t('def.logout')
        </x-button>
    </div>
    @stack('right-sidebar')
</div>
