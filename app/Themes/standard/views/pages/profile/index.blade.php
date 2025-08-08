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
                    <header class="profile__banner-wrapper">
                        <div class="profile__banner-wrapper-inner">
                            <img src="{{ url($user->banner) }}"
                                alt="{{ __('profile.banner_alt', ['name' => $user->name]) }}" class="profile__banner"
                                loading="lazy" data-profile-banner="{{ $user->banner }}"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='block';" />
                            <div class="profile__banner-fallback"
                                style="display: none; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 200px; width: 100%;">
                            </div>
                        </div>

                        @if (isset($sections['profile_banner_wrapper']))
                            {!! $sections['profile_banner_wrapper'] !!}
                        @endif
                    </header>

                    @auth
                        @if (!$user->isTemporary())
                            @if ((user()->can('admin.users') && user()->can($user)) || $user->id === user()->id)
                                <x-button size="small"
                                    href="{{ url($user->id !== user()->id ? 'admin/users/' . $user->id . '/edit' : 'profile/settings') }}"
                                    class="profile__edit-btn" hx-boost="{{ $user->id !== user()->id ? 'false' : 'true' }}"
                                    hx-target="#main" hx-swap="outerHTML transition:true" size="medium">
                                    <x-icon path="ph.regular.pencil" />
                                    @t('def.edit')
                                </x-button>
                            @endif
                        @endif
                    @endauth

                    <div class="profile__main">
                        <aside class="profile__sidebar" role="complementary">
                            <div class="profile__hero">
                                <div class="profile__hero-avatar">
                                    <img src="{{ url($user->avatar) }}"
                                        alt="{{ __('profile.avatar_alt', ['name' => $user->name]) }}"
                                        class="profile__avatar" loading="lazy" data-profile-avatar="{{ $user->avatar }}"
                                        onerror="this.src='{{ asset('assets/img/no-avatar.webp') }}'; this.onerror=null;"
                                        itemprop="image" />
                                </div>

                                <h1 class="profile__hero-name" data-profile-name="{{ $user->name }}" itemprop="name">
                                    {{ $user->name }}
                                    @if ($user->isOnline())
                                        <span class="profile__status profile__status--online"
                                            data-profile-status="{{ __('def.online') }}"
                                            aria-label="{{ __('def.online') }}">{{ __('def.online') }}</span>
                                    @endif
                                </h1>

                                @if (sizeof($user->roles))
                                    <div class="profile__roles" itemprop="jobTitle">
                                        <ul class="profile__roles-list" role="list">
                                            @foreach ($user->roles as $role)
                                                <li class="profile__role">
                                                    <span class="profile__role-square"
                                                        style="background: {{ $role->color }}" aria-hidden="true">
                                                    </span>
                                                    <span class="profile__role-name">
                                                        {{ $role->name }}
                                                    </span>
                                                </li>
                                            @endforeach
                                        </ul>

                                        @if (isset($sections['profile_roles']))
                                            {!! $sections['profile_roles'] !!}
                                        @endif
                                    </div>
                                @endif

                                <div class="profile__hero-meta">
                                    @if (!$user->isOnline())
                                        <span class="profile__status profile__status--offline">
                                            {{ $user->getLastLoggedPhrase() }}
                                        </span>
                                    @endif
                                    <span itemprop="memberOf" itemscope itemtype="https://schema.org/Organization">
                                        <x-icon path="ph.regular.calendar" aria-hidden="true" />
                                        <time datetime="{{ carbon($user->createdAt)->toISOString() }}"
                                            itemprop="foundingDate">
                                            {{ __('profile.member_since', [':date' => carbon($user->createdAt)->format('d.m.Y')]) }}
                                        </time>
                                    </span>
                                    @if ($user->login)
                                        <span itemprop="alternateName">
                                            <x-icon path="ph.regular.at" aria-hidden="true" />
                                            {{ $user->login }}
                                        </span>
                                    @endif
                                </div>

                                @if ($user->socialNetworks)
                                    <nav class="profile__socials" aria-label="{{ __('profile.social_networks') }}">
                                        <ul class="profile__socials-list" role="list">
                                            @foreach ($user->socialNetworks as $social)
                                                @if (!$social->hidden || (user()->isLoggedIn() && (user()->can('admin.users') || user()->id === $row->id)))
                                                    <li class="profile__socials-item-wrapper">
                                                        <a href="{{ $social->url }}" class="profile__socials-item"
                                                            target="_blank" data-tooltip="{{ $social->name }}"
                                                            rel="noopener nofollow"
                                                            aria-label="{{ __('profile.visit_social', ['network' => $social->name]) }}"
                                                            itemprop="sameAs">
                                                            <div class="profile__socials-item-icon">
                                                                <x-icon path="{{ $social->socialNetwork->icon }}"
                                                                    aria-hidden="true" />
                                                            </div>
                                                        </a>
                                                    </li>
                                                @endif
                                            @endforeach
                                        </ul>
                                    </nav>
                                @endif

                                @if (isset($sections['profile_hero_info']))
                                    {!! $sections['profile_hero_info'] !!}
                                @endif
                            </div>

                            @if (isset($sections['profile_sidebar']))
                                {!! $sections['profile_sidebar'] !!}
                            @endif
                        </aside>

                        <section class="profile__content-wrapper" role="main">
                            @if ($tabs->count() === 0 && user()->can('admin.boss'))
                                <x-alert type="info" onlyBorders style="margin-top: 4.5rem;" withClose="false">
                                    {!! __('profile.no_profile_modules_info', [':link' => url('/admin/catalog')]) !!}
                                </x-alert>
                            @else
                                <div class="profile__content">
                                    <nav aria-label="{{ __('profile.profile_tabs') }}">
                                        <x-tabs name="profile-tabs" hx-push-url="true">
                                            <x-slot:headings>
                                                @foreach ($tabs as $key => $tab)
                                                    <x-tab-heading name="{{ $tab['path'] }}"
                                                        url="{{ url('profile/' . $user->getUrl())->addParams(['tab' => $tab['path']]) }}"
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
                                                    active="{{ $tab['path'] === $activePath }}" role="tabpanel"
                                                    aria-labelledby="tab-{{ $tab['path'] }}"
                                                    id="tabpanel-{{ $tab['path'] }}">
                                                    @if (isset($sections['profile_tab_content_' . $tab['path']]))
                                                        {!! $sections['profile_tab_content_' . $tab['path']] !!}
                                                    @endif
                                                    @if ($tab['path'] === $activePath)
                                                        {!! $initialTabHtml ?? '' !!}
                                                    @else
                                                        <div class="row gx-3 gy-3" role="region"
                                                            aria-label="{{ __('profile.loading_content') }}">
                                                            @foreach ([8, 4, 12] as $colSize)
                                                                <div class="col-md-{{ $colSize }}">
                                                                    <div class="skeleton tabs-skeleton w-100"
                                                                        style="height: 200px"
                                                                        aria-label="{{ __('profile.loading') }}"
                                                                        role="status">
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
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
