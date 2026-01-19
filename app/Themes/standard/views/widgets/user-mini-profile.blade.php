@if (user()->isLoggedIn())
    <div class="user-mini-profile">
        <div class="user-mini-profile-content">
            @php
                $roles = $user->roles;
                $primaryRole = $roles[0] ?? null;
            @endphp
            <div class="user-mini-profile-main" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true">
                <div class="user-mini-profile-cover">
                    <img src="{{ asset($user->banner ?? config('profile.default_banner')) }}" alt="{{ $user->name }}"
                        class="user-mini-profile-img" loading="lazy">
                    <div class="user-mini-profile-cover-noise"></div>
                </div>

                <div class="user-mini-profile-body">
                    <div class="user-mini-profile-top">
                        <div class="user-mini-profile-avatar-wrapper">
                            <a href="{{ url('profile/'.$user->getUrl()) }}" class="user-mini-profile-avatar">
                                <img src="{{ asset($user->avatar ?? config('profile.default_avatar')) }}"
                                    alt="{{ $user->name }}" class="user-mini-profile-img" loading="lazy">
                            </a>
                            @if (user()->can('admin'))
                                <span class="user-mini-profile-crown" aria-hidden="true">
                                    <x-icon path="ph.bold.crown-bold" />
                                </span>
                            @endif
                        </div>

                        <div class="user-mini-profile-rank">
                            <div class="user-mini-profile-rank-label">{{ __('def.roles') }}</div>
                            <div class="user-mini-profile-rank-badge"
                                @if ($primaryRole) style="--role-color: {{ $primaryRole->color }}" @endif>
                                <span class="user-mini-profile-rank-dot{{ $primaryRole ? '' : ' is-muted' }}"></span>
                                <span>{{ $primaryRole->name ?? __('def.user') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="user-mini-profile-info">
                        <a href="{{ url('profile/'.$user->getUrl()) }}"
                            class="user-mini-profile-name">{{ $user->name }}</a>
                        <div class="user-mini-profile-presence">
                            <span
                                class="user-mini-profile-presence-dot {{ $user->isOnline() ? 'online' : 'offline' }}"></span>
                            <span
                                class="user-mini-profile-presence-text">{{ $user->isOnline() ? __('def.online') : __('def.not_online') }}</span>
                        </div>
                    </div>

                    @if (count($roles) > 0)
                        <div class="user-mini-profile-roles">
                            @if(count($roles) > 2)
                                @for($i = 0; $i < count($roles); $i++)
                                    @if($i >= 2)
                                        @break
                                    @endif

                                    @php
                                        $role = $roles[$i];
                                    @endphp

                                    <div class="user-mini-profile-role">
                                        <span class="user-mini-profile-role-square"
                                            style="background: {{ $role->color }}"></span>
                                        <span class="user-mini-profile-role-name">{{ $role->name }}</span>
                                    </div>
                                @endfor
                                <div class="d-flex align-center">
                                    <div class="user-mini-profile-role cursor-pointer"
                                        data-tooltip="#user_roles_{{ $user->id }}">
                                        <span class="user-mini-profile-role-name">... {{ count($roles) - 2 }}
                                            {{ __('def.roles') }}</span>
                                    </div>
                                    <div id="user_roles_{{ $user->id }}" class="d-none">
                                        <ul class="user-roles-list">
                                            @for($a = 0; $a < count($roles); $a++)
                                                @if($a < 2)
                                                    @continue
                                                @endif

                                                @php
                                                    $role = $roles[$a];
                                                @endphp
                                                <li>
                                                    <div class="user-mini-profile-role">
                                                        <span class="user-mini-profile-role-square"
                                                            style="background: {{ $role->color }}"></span>
                                                        <span class="user-mini-profile-role-name">{{ $role->name }}</span>
                                                    </div>
                                                </li>
                                            @endfor
                                        </ul>
                                    </div>
                                </div>
                            @else
                                @foreach ($roles as $key => $role)
                                    <div class="user-mini-profile-role">
                                        <span class="user-mini-profile-role-square"
                                            style="background: {{ $role->color }}"></span>
                                        <span class="user-mini-profile-role-name">{{ $role->name }}</span>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <div class="user-mini-profile-balance-card">
                <div class="balance-info">
                    <div class="balance-label">{{ __('def.my_balance') }}</div>
                    <div class="balance-amount">
                        <span class="balance-value">{{ number_format($user->balance, 2) }}</span>
                        <span class="balance-currency">{{ config('lk.currency_view') }}</span>
                    </div>
                </div>

                @if (config('lk.only_modal'))
                    <a class="balance-action" data-modal-open="lk-modal">
                        <x-icon path="ph.regular.plus" />
                        {{ __('def.top_up') }}
                    </a>
                @else
                    <a href="{{ url('lk') }}" class="balance-action" hx-boost="true" hx-target="#main"
                        hx-swap="outerHTML transition:true">
                        <x-icon path="ph.regular.plus" />
                        {{ __('def.top_up') }}
                    </a>
                @endif
            </div>

            <div class="user-mini-profile-actions" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true">
                <x-button href="{{ url('profile/'.$user->getUrl()) }}" class="user-mini-profile-action"
                    type="outline-primary">
                    <x-icon path="ph.regular.user-circle" />
                    <span>{{ __('def.my_profile') }}</span>
                    <span class="user-mini-profile-action-chevron">
                        <x-icon path="ph.regular.caret-right" />
                    </span>
                </x-button>

                <x-button href="{{ url('profile/settings') }}" class="user-mini-profile-action" type="outline-primary">
                    <x-icon path="ph.regular.gear" />
                    <span>{{ __('def.settings') }}</span>
                    <span class="user-mini-profile-action-chevron">
                        <x-icon path="ph.regular.caret-right" />
                    </span>
                </x-button>

                @if (user()->can('admin'))
                    <x-button href="{{ url('admin') }}" class="user-mini-profile-action" type="outline-primary"
                        hx-boost="false">
                        <x-icon path="ph.regular.shield" />
                        <span>{{ __('def.admin_panel') }}</span>
                        <span class="user-mini-profile-action-chevron">
                            <x-icon path="ph.regular.caret-right" />
                        </span>
                    </x-button>
                @endif

                <form method="POST" action="{{ url('logout') }}" class="user-mini-profile-action-form" hx-boost="false">
                    @csrf
                    <x-button class="w-100 user-mini-profile-action user-mini-profile-action--danger" type="outline-error"
                        submit="true">
                        <x-icon path="ph.regular.sign-out" />
                        <span>{{ __('def.logout') }}</span>
                    </x-button>
                </form>
            </div>
        </div>
    </div>
@else
    <div class="user-mini-profile guest-profile">
        <div class="user-mini-profile-content">
            <div class="guest-profile-header">
                <div class="guest-profile-avatar">
                    <x-icon path="ph.regular.user-circle" />
                </div>
                <div class="guest-profile-info">
                    <h4>{{ __('auth.guest') }}</h4>
                    <p>{{ __('auth.guest_description') }}</p>
                </div>
            </div>

            @php
                $socialNetworks = social()->getAll();
                $socialCount = sizeof($socialNetworks);
            @endphp

            @if (! config('auth.only_social', false) || (config('auth.only_social') && $socialCount === 0))
                <div class="guest-profile-actions" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true">
                    @if (config('auth.only_modal'))
                        <x-button class="guest-profile-action" type="accent" data-modal-open="auth-modal">
                            <x-icon path="ph.regular.sign-in" />
                            <span>{{ __('def.login') }}</span>
                        </x-button>
                        <x-button class="guest-profile-action" type="outline-primary" data-modal-open="register-modal">
                            <x-icon path="ph.regular.user-plus" />
                            <span>{{ __('auth.register') }}</span>
                        </x-button>
                    @else
                        <x-button href="{{ url('login') }}" class="guest-profile-action" type="accent">
                            <x-icon path="ph.regular.sign-in" />
                            <span>{{ __('def.login') }}</span>
                        </x-button>
                        <x-button href="{{ url('register') }}" class="guest-profile-action" type="outline-primary">
                            <x-icon path="ph.regular.user-plus" />
                            <span>{{ __('auth.register') }}</span>
                        </x-button>
                    @endif
                </div>
            @elseif (config('auth.only_social', false) && $socialCount === 1)
                @php
                    $item = social()->toDisplay();
                    $key = key($item);
                    $icon = $item[$key];
                @endphp
                <div class="guest-profile-actions" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true">
                    <x-button href="{{ url('social/'.$key) }}" class="guest-profile-action" type="accent" hx-boost="false">
                        <x-icon path="{!! $icon !!}" />
                        <span>{{ __('auth.social.auth_via', [':social' => $key]) }}</span>
                    </x-button>
                </div>
            @elseif (config('auth.only_social', false) && $socialCount > 1)
                <div class="guest-profile-actions" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true">
                    @if (config('auth.only_modal'))
                        <x-button class="guest-profile-action" type="accent" data-modal-open="auth-modal">
                            <x-icon path="ph.regular.sign-in" />
                            <span>{{ __('def.login') }}</span>
                        </x-button>
                    @else
                        <x-button href="{{ url('login') }}" class="guest-profile-action" type="accent">
                            <x-icon path="ph.regular.sign-in" />
                            <span>{{ __('def.login') }}</span>
                        </x-button>
                    @endif
                </div>
            @endif

            @if ($socialCount > 0 && (! config('auth.only_social') || (config('auth.only_social') && $socialCount > 1)))
                <div class="guest-profile-socials">
                    <div class="guest-profile-socials-label">{{ __('auth.social.quick_login') }}</div>
                    <div class="guest-profile-socials-buttons">
                        @foreach ($socialNetworks as $key => $item)
                            <a href="{{ url('social/'.$key) }}" class="guest-profile-social-btn" title="{{ $key }}"
                                data-tooltip="{{ $item['entity']->key }}">
                                <x-icon path="{!! $item['entity']->icon !!}" />
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
@endif
