@extends('flute::layouts.app')

@section('title')
    {{ __('def.profile') . " - {$user->name}" }}
@endsection

@push('content')
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                @auth
                    @if (!$user->password && user()->id === $user->id && !config('auth.only_social'))
                        <x-alert type="warning" onlyBorders withClose="false" hx-boost="true" hx-target="#main"
                            hx-swap="outerHTML transition:true">
                            {!! __('profile.protection_warning', [':link' => url('/profile/settings?tab=main#password-settings')]) !!}
                        </x-alert>
                    @endif
                @endauth

                @auth
                    @if (!$user->verified && config('auth.registration.confirm_email') && user()->id === $user->id)
                        <x-alert type="warning" onlyBorders withClose="false" hx-boost="true" hx-target="#main"
                            hx-swap="outerHTML transition:true">
                            {!! __('profile.verification_warning', [':link' => url('/profile/settings')]) !!}
                        </x-alert>
                    @endif
                @endauth

                @auth
                    @if ($user->hidden && user()->id === $user->id)
                        <x-alert type="warning" onlyBorders withClose="false">
                            {!! __('profile.hidden_warning') !!}
                        </x-alert>
                    @endif
                @endauth

                @stack('profile_alerts_wrapper')
                @if (isset($sections['profile_alerts_wrapper']))
                    {!! $sections['profile_alerts_wrapper'] !!}
                @endif

                <article class="profile" data-profile-id="{{ $user->id }}" itemscope
                    itemtype="https://schema.org/Person">

                    {{-- Banner wrapper slot (modules can inject here, e.g. SteamInfo) --}}
                    @if (isset($sections['profile_banner_wrapper']))
                        <div class="d-none">{!! $sections['profile_banner_wrapper'] !!}</div>
                    @endif

                    {{-- Main: sidebar + content --}}
                    <div class="profile__main">

                        {{-- ═══ SIDEBAR ═══ --}}
                        <aside class="profile__sidebar" role="complementary">

                            {{-- Slot: custom block above hero --}}
                            @stack('profile_block_above_hero')
                            @if (isset($sections['profile_block_above_hero']))
                                <div class="profile__custom-block profile__custom-block--top">
                                    {!! $sections['profile_block_above_hero'] !!}
                                </div>
                            @endif

                            {{-- Hero card --}}
                            <div class="profile__hero {{ isset($sections['profile_frame_class']) ? 'profile__frame profile__frame--active' : '' }}"
                                @if (isset($sections['profile_frame_color']))
                                    style="--frame-color: {{ $sections['profile_frame_color'] }}"
                                @endif
                            >
                                {{-- Mini banner inside card --}}
                                <div class="profile__hero-banner">
                                    <img src="{{ url($user->banner) }}" alt=""
                                        loading="lazy"
                                        onerror="this.style.display='none';" />
                                </div>

                                <div class="profile__hero-body">
                                    {{-- Actions — edit + admin --}}
                                    @auth
                                        @if (!$user->isTemporary())
                                            <div class="profile__hero-actions">
                                                @if ((user()->can('admin.users') && user()->can($user)) || $user->id === user()->id)
                                                    <a href="{{ url($user->id !== user()->id ? 'admin/users/' . $user->id . '/edit' : 'profile/settings') }}"
                                                        class="profile__hero-action-btn"
                                                        @if ($user->id === user()->id) hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true" @endif
                                                        data-tooltip="@t('def.edit')">
                                                        <x-icon path="ph.regular.pencil" />
                                                    </a>
                                                @endif

                                                @stack('profile_actions')
                                                @if (isset($sections['profile_actions']))
                                                    {!! $sections['profile_actions'] !!}
                                                @endif

                                                @if (user()->can('admin.users') && user()->can($user) && $user->id !== user()->id)
                                                    <button type="button" class="profile__hero-action-btn"
                                                        data-dropdown-open="profile-admin-dropdown-{{ $user->id }}"
                                                        data-tooltip="@t('def.actions')">
                                                        <x-icon path="ph.regular.dots-three" />
                                                    </button>
                                                    <div class="profile__admin-dropdown" data-dropdown="profile-admin-dropdown-{{ $user->id }}">
                                                        <button type="button" class="profile__admin-dropdown-item"
                                                            data-modal-open="profile-add-balance-modal">
                                                            <x-icon path="ph.regular.plus-circle" />
                                                            <span>@t('profile.admin_actions.add_balance')</span>
                                                        </button>
                                                        <button type="button" class="profile__admin-dropdown-item"
                                                            data-modal-open="profile-remove-balance-modal">
                                                            <x-icon path="ph.regular.minus-circle" />
                                                            <span>@t('profile.admin_actions.remove_balance')</span>
                                                        </button>
                                                        <div class="profile__admin-dropdown-divider"></div>
                                                        <button type="button" class="profile__admin-dropdown-item"
                                                            hx-post="{{ url('api/profile/' . $user->id . '/toggle-verified') }}"
                                                            hx-swap="none"
                                                            hx-on::after-request="if(event.detail.successful) { setTimeout(() => location.reload(), 300); }">
                                                            @if ($user->verified)
                                                                <x-icon path="ph.regular.seal-warning" />
                                                                <span>@t('profile.admin_actions.unverify_user')</span>
                                                            @else
                                                                <x-icon path="ph.regular.seal-check" />
                                                                <span>@t('profile.admin_actions.verify_user')</span>
                                                            @endif
                                                        </button>
                                                        <button type="button" class="profile__admin-dropdown-item profile__admin-dropdown-item--warning"
                                                            hx-post="{{ url('api/profile/' . $user->id . '/clear-sessions') }}"
                                                            hx-trigger="confirmed" hx-swap="none"
                                                            hx-flute-confirm="{{ __('profile.admin_actions.clear_sessions_confirm') }}"
                                                            hx-flute-confirm-type="warning"
                                                            hx-on::after-request="if(event.detail.successful) { setTimeout(() => location.reload(), 300); }">
                                                            <x-icon path="ph.regular.sign-out" />
                                                            <span>@t('profile.admin_actions.clear_sessions')</span>
                                                        </button>
                                                        <div class="profile__admin-dropdown-divider"></div>
                                                        @if ($user->isBlocked())
                                                            <button type="button" class="profile__admin-dropdown-item profile__admin-dropdown-item--success"
                                                                hx-post="{{ url('api/profile/' . $user->id . '/unban') }}"
                                                                hx-trigger="confirmed" hx-swap="none"
                                                                hx-flute-confirm="{{ __('profile.admin_actions.unban_confirm') }}"
                                                                hx-flute-confirm-type="success"
                                                                hx-on::after-request="if(event.detail.successful) { setTimeout(() => location.reload(), 300); }">
                                                                <x-icon path="ph.regular.lock-open" />
                                                                <span>@t('profile.admin_actions.unban_user')</span>
                                                            </button>
                                                        @else
                                                            <button type="button" class="profile__admin-dropdown-item profile__admin-dropdown-item--danger"
                                                                data-modal-open="profile-ban-modal">
                                                                <x-icon path="ph.regular.prohibit" />
                                                                <span>@t('profile.admin_actions.ban_user')</span>
                                                            </button>
                                                        @endif
                                                    </div>

                                                    <x-modal id="profile-add-balance-modal" :title="__('profile.admin_actions.add_balance')"
                                                        :loadUrl="url('api/profile/' . $user->id . '/modal/add-balance')" size="small" />
                                                    <x-modal id="profile-remove-balance-modal" :title="__('profile.admin_actions.remove_balance')"
                                                        :loadUrl="url('api/profile/' . $user->id . '/modal/remove-balance')" size="small" />
                                                    <x-modal id="profile-ban-modal" :title="__('profile.admin_actions.ban_user')"
                                                        :loadUrl="url('api/profile/' . $user->id . '/modal/ban')" size="small" />
                                                @endif
                                            </div>
                                        @endif
                                    @endauth

                                    {{-- Avatar --}}
                                    <div class="profile__hero-avatar">
                                        <img src="{{ url($user->avatar) }}"
                                            alt="{{ __('profile.avatar_alt', ['name' => $user->name]) }}"
                                            loading="lazy" data-profile-avatar="{{ $user->avatar }}"
                                            onerror="this.src='{{ asset('assets/img/no-avatar.webp') }}'; this.onerror=null;"
                                            itemprop="image" />
                                    </div>

                                    {{-- Name + verified --}}
                                    <h1 class="profile__hero-name" data-profile-name="{{ $user->name }}" itemprop="name">
                                        <span class="profile__hero-name-row">
                                            {{ $user->name }}
                                            @auth
                                                @if (user()->can('admin.boss'))
                                                    <button type="button"
                                                        class="verified-badge {{ $user->approved ? '' : 'verified-badge--ghosted' }}"
                                                        data-tooltip="{{ $user->approved ? __('profile.admin_actions.unapprove_user') : __('profile.admin_actions.approve_user') }}"
                                                        hx-flute-confirm="{{ $user->approved ? __('profile.admin_actions.unapprove_confirm') : __('profile.admin_actions.approve_confirm') }}"
                                                        hx-flute-confirm-type="{{ $user->approved ? 'warning' : 'success' }}"
                                                        hx-post="{{ url('api/profile/' . $user->id . '/toggle-approved') }}"
                                                        hx-trigger="confirmed"
                                                        hx-swap="none"
                                                        hx-on::after-request="if(event.detail.successful) { setTimeout(() => location.reload(), 300); }">
                                                        <x-icon path="ph.bold.seal-check-bold" />
                                                    </button>
                                                @elseif ($user->approved)
                                                    <span class="verified-badge" data-tooltip="{{ __('def.approved') }}">
                                                        <x-icon path="ph.bold.seal-check-bold" />
                                                    </span>
                                                @endif
                                            @else
                                                @if ($user->approved)
                                                    <span class="verified-badge" data-tooltip="{{ __('def.approved') }}">
                                                        <x-icon path="ph.bold.seal-check-bold" />
                                                    </span>
                                                @endif
                                            @endauth

                                            {{-- Slot: custom tag near name --}}
                                            @stack('profile_name_tag')
                                            @if (isset($sections['profile_name_tag']))
                                                {!! $sections['profile_name_tag'] !!}
                                            @endif
                                        </span>
                                    </h1>

                                    {{-- Slot: description above meta --}}
                                    @stack('profile_description_top')
                                    @if (isset($sections['profile_description_top']))
                                        <div class="profile__description profile__description--top">
                                            {!! $sections['profile_description_top'] !!}
                                        </div>
                                    @endif

                                    {{-- Meta: online + location --}}
                                    <div class="profile__hero-meta">
                                        <div class="profile__hero-meta-line">
                                            @if ($user->isOnline())
                                                <span class="profile__status profile__status--online">{{ __('def.online') }}</span>
                                            @else
                                                <span class="profile__status profile__status--offline">{{ $user->getLastLoggedPhrase() }}</span>
                                            @endif
                                            <span class="profile__hero-dot"></span>
                                            <span itemprop="memberOf" itemscope itemtype="https://schema.org/Organization">
                                                <x-icon path="ph.regular.calendar" aria-hidden="true" />
                                                <time datetime="{{ carbon($user->createdAt)->toISOString() }}" itemprop="foundingDate">
                                                    {{ __('profile.member_since', [':date' => carbon($user->createdAt)->format('d.m.Y')]) }}
                                                </time>
                                            </span>
                                        </div>
                                        @if ($user->login)
                                            <div class="profile__hero-meta-line">
                                                <span itemprop="alternateName">
                                                    <x-icon path="ph.regular.at" aria-hidden="true" />
                                                    {{ $user->login }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Roles --}}
                                    @if (sizeof($user->roles))
                                        <div class="profile__roles" itemprop="jobTitle">
                                            <div class="role-badges" role="list">
                                                @foreach ($user->roles as $role)
                                                    <x-role-badge :role="$role" mode="full" />
                                                @endforeach
                                            </div>
                                            @if (isset($sections['profile_roles']))
                                                {!! $sections['profile_roles'] !!}
                                            @endif
                                        </div>
                                    @endif

                                    {{-- Slot: custom button --}}
                                    @stack('profile_hero_button')
                                    @if (isset($sections['profile_hero_button']))
                                        <div style="margin-top: var(--space-sm)">
                                            {!! $sections['profile_hero_button'] !!}
                                        </div>
                                    @endif

                                    {{-- Slot: description below roles --}}
                                    @stack('profile_description_bottom')
                                    @if (isset($sections['profile_description_bottom']))
                                        <div class="profile__description profile__description--bottom">
                                            {!! $sections['profile_description_bottom'] !!}
                                        </div>
                                    @endif

                                    {{-- Slot: modules inject IDs and extra info here (e.g. SteamInfo) --}}
                                    @if (isset($sections['profile_hero_info']))
                                        {!! $sections['profile_hero_info'] !!}
                                    @endif
                                </div>
                            </div>

                            {{-- Connected socials --}}
                            @if ($user->socialNetworks && sizeof($user->socialNetworks) > 0)
                                <div class="profile__section">
                                    <div class="profile__section-title">@t('profile.social_networks')</div>
                                    @foreach ($user->socialNetworks as $social)
                                        @if (!$social->hidden || (user()->isLoggedIn() && (user()->can('admin.users') || user()->can('admin.users.view') || user()->id === $user->id)))
                                            @php
                                                $socialUrl = $social->url;
                                                if ($social->socialNetwork?->key === 'Discord' && !empty($social->value)) {
                                                    $socialUrl = 'https://discord.com/users/' . $social->value;
                                                }
                                            @endphp
                                            @php
                                                $socialPhoto = $social->getAdditional()['photoUrl'] ?? null;
                                            @endphp
                                            <div class="profile__social-card">
                                                <div class="profile__social-card-header">
                                                    @if ($socialPhoto)
                                                        <img src="{{ $socialPhoto }}" alt=""
                                                            class="profile__social-card-avatar"
                                                            loading="lazy"
                                                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" />
                                                        <span class="profile__social-card-icon" style="display: none">
                                                            <x-icon path="{{ $social->socialNetwork?->icon }}" />
                                                        </span>
                                                    @else
                                                        <span class="profile__social-card-icon">
                                                            <x-icon path="{{ $social->socialNetwork?->icon }}" />
                                                        </span>
                                                    @endif
                                                    <div class="profile__social-card-info">
                                                        <div class="profile__social-card-name">{{ $social->name ?: $social->socialNetwork?->key }}</div>
                                                        <div class="profile__social-card-platform">{{ $social->socialNetwork?->key }}</div>
                                                    </div>
                                                    @if ($socialUrl)
                                                        <a href="{{ $socialUrl }}" target="_blank" rel="noopener nofollow"
                                                            class="profile__social-card-link"
                                                            aria-label="{{ __('profile.visit_social', ['network' => $social->name]) }}">
                                                            <x-icon path="ph.regular.arrow-square-out" />
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif

                            {{-- Slot: sidebar sections from modules --}}
                            @if (isset($sections['profile_sidebar']))
                                {!! $sections['profile_sidebar'] !!}
                            @endif
                            @stack('profile_sidebar')

                            {{-- Slot: custom block below sidebar --}}
                            @stack('profile_block_below_sidebar')
                            @if (isset($sections['profile_block_below_sidebar']))
                                <div class="profile__custom-block profile__custom-block--bottom">
                                    {!! $sections['profile_block_below_sidebar'] !!}
                                </div>
                            @endif
                        </aside>

                        {{-- ═══ CONTENT ═══ --}}
                        <section class="profile__content-wrapper" role="main">
                            @if ($tabs->count() === 0 && user()->can('admin.boss'))
                                <x-alert type="info" onlyBorders style="margin-top: 4.5rem;" withClose="false">
                                    {!! __('profile.no_profile_modules_info', [':link' => url('https://flute-cms.com/market')]) !!}
                                </x-alert>
                            @else
                                <div class="profile__content">
                                    <nav aria-label="{{ __('profile.profile_tabs') }}">
                                        <x-tabs name="profile-tabs" variant="segment" hx-push-url="true">
                                            <x-slot:headings>
                                                @foreach ($tabs as $key => $tab)
                                                    <x-tab-heading name="{{ $tab['path'] }}"
                                                        url="{{ $tab['path'] === $activePath ? '' : url('profile/' . $user->getUrl())->addParams(['tab' => $tab['path']]) }}"
                                                        withoutHtmx="{{ $tab['path'] === $activePath }}"
                                                        active="{{ $tab['path'] === $activePath }}"
                                                        aria-label="{{ __($tab['title']) }}">
                                                        @if ($tab['icon'])
                                                            <x-icon path="{{ $tab['icon'] }}" aria-hidden="true" />
                                                        @endif
                                                        {!! __($tab['title']) !!}
                                                    </x-tab-heading>
                                                @endforeach
                                            </x-slot:headings>
                                        </x-tabs>
                                    </nav>

                                    <div class="profile__overflow-tabs">
                                        <x-tab-body name="profile-tabs">
                                            @foreach ($tabs as $key => $tab)
                                                <x-tab-content name="{{ $tab['path'] }}"
                                                    active="{{ $tab['path'] === $activePath }}">
                                                    @if (isset($sections['profile_tab_content_' . $tab['path']]))
                                                        {!! $sections['profile_tab_content_' . $tab['path']] !!}
                                                    @endif
                                                    @if ($tab['path'] === $activePath)
                                                        {!! $initialTabHtml ?? '' !!}
                                                    @else
                                                        @include('flute::partials.tab-skeleton')
                                                    @endif
                                                </x-tab-content>
                                            @endforeach
                                        </x-tab-body>
                                    </div>
                                </div>
                            @endif
                        </section>

                    </div>
                </article>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    @at(tt('assets/scripts/pages/profile.js'))
@endpush
