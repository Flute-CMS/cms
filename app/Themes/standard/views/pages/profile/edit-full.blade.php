@extends('flute::layouts.app')

@section('title')
    {{ __('profile.edit.title') }}
@endsection

@push('content')
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <section class="profile-edit__main">
                    <div class="profile-edit__hero">
                        <div class="profile-edit__hero-avatar" id="avatar-container">
                            <img src="{{ asset($user->avatar ?? config('profile.default_avatar')) }}"
                                alt="{{ $user->name }}" loading="lazy" id="avatar-image">
                        </div>

                        <h3>{{ $user->name }}</h3>
                        @if ($user->email)
                            <p>{{ $user->email }}</p>
                        @endif
                    </div>
                    <div class="profile-edit__main-blocks" hx-boost="true" hx-target="#main"
                        hx-swap="outerHTML transition:true">
                        <div class="profile-edit__main-block">
                            <div class="profile-edit__main-block-header">
                                <h4>{{ __('profile.edit.main.info_title') }}</h4>
                                <p>{{ __('profile.edit.main.info_description') }}</p>
                            </div>
                            <div class="profile-edit__main-block-body">
                                <div class="profile-edit__field">
                                    <div class="profile-edit__field-name">{{ __('profile.edit.main.fields.name') }}</div>
                                    <div class="profile-edit__field-value">{{ $user->name }}</div>
                                    <a href="{{ url('profile/settings?tab=main') }}" class="profile-edit__field-icon">
                                        <x-icon path="ph.regular.pencil" />
                                    </a>
                                </div>
                                <div class="profile-edit__field">
                                    <div class="profile-edit__field-name">{{ __('profile.edit.main.fields.email') }}</div>
                                    <div class="profile-edit__field-value">
                                        @if ($user->email)
                                            {{ $user->email }}

                                            @if ($user->verified)
                                                <div class="profile-edit__verified"
                                                    data-tooltip="{{ __('profile.edit.main.fields.email_verified') }}">
                                                    <x-icon path="ph.bold.check-bold" />
                                                </div>
                                            @else
                                                <div class="profile-edit__not-verified"
                                                    data-tooltip="{{ __('profile.edit.main.fields.email_not_verified') }}">
                                                    <x-icon path="ph.regular.x" />
                                                </div>
                                                @if (config('auth.registration.confirm_email'))
                                                    <x-button type="primary" size="tiny" hx-post="{{ url('profile/verify-email') }}" hx-swap="none">
                                                        {{ __('profile.edit.main.fields.verify_email') }}
                                                    </x-button>
                                                @endif
                                            @endif
                                        @else
                                            <p class="profile-edit__error">
                                                {{ __('profile.edit.main.fields.password_not_provided') }}
                                            </p>
                                        @endif
                                    </div>
                                    <a href="{{ url('profile/settings?tab=main') }}" class="profile-edit__field-icon">
                                        <x-icon path="ph.regular.pencil" />
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="profile-edit__main-block">
                            <div class="profile-edit__main-block-header">
                                <h4>{{ __('profile.edit.main.fields.password') }}</h4>
                                <p>{{ __('profile.edit.main.password_description') }}</p>
                            </div>
                            <div class="profile-edit__main-block-body">
                                <div class="profile-edit__field">
                                    <div class="profile-edit__field-value value-password">
                                        @if ($user->password)
                                            *********
                                        @else
                                            <p class="profile-edit__error">
                                                {{ __('profile.edit.main.fields.password_not_set') }}
                                            </p>
                                        @endif

                                        @if ($user->password_updated_at)
                                            <p class="profile-edit__last-changed">
                                                {{ __('profile.edit.main.fields.last_changed') }}
                                                {{ carbon($user->password_updated_at)->diffForHumans() }}
                                            </p>
                                        @endif
                                    </div>
                                    <a href="{{ url('profile/settings?tab=main#password-settings') }}"
                                        class="profile-edit__field-icon">
                                        <x-icon path="ph.regular.pencil" />
                                    </a>
                                </div>
                            </div>
                        </div>
                        @if (config('auth.two_factor.enabled'))
                            <div class="profile-edit__main-block">
                                <div class="profile-edit__main-block-header">
                                    <h4>{{ __('auth.two_factor.title') }}</h4>
                                    <p>{{ __('auth.two_factor.description') }}</p>
                                </div>
                                <div class="profile-edit__main-block-body">
                                    <div class="profile-edit__field">
                                        <div class="profile-edit__field-value">
                                            @if ($user->hasTwoFactorEnabled())
                                                <x-badge type="success" icon="ph.regular.shield-check">
                                                    {{ __('profile.two_factor.status_enabled') }}
                                                </x-badge>
                                            @else
                                                <x-badge type="error" icon="ph.regular.shield">
                                                    {{ __('profile.two_factor.status_disabled') }}
                                                </x-badge>
                                            @endif
                                        </div>
                                        <a href="{{ url('profile/settings?tab=main#two-factor-settings') }}"
                                            class="profile-edit__field-icon">
                                            <x-icon path="ph.regular.pencil" />
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    <h4 class="mt-5">{{ __('profile.edit.settings.title') }}</h4>
                    <div class="profile-edit__blocks" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true">
                        @foreach ($tabs as $key => $tab)
                            <a href="{{ url('profile/settings')->addParams(['tab' => $tab['path']]) }}"
                                class="profile-edit__block">
                                <div class="profile-edit__block-header">
                                    @if ($tab['icon'])
                                        <x-icon path="{{ $tab['icon'] }}" />
                                    @endif
                                    <h5>
                                        {!! __($tab['title']) !!}
                                    </h5>

                                    <x-icon path="ph.regular.arrow-right" class="profile-edit__block-arrow" />
                                </div>
                                @if ($tab['description'])
                                    <div class="profile-edit__block-description">
                                        {!! __($tab['description']) !!}
                                    </div>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </section>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    @at(tt('assets/scripts/pages/profile-edit.js'))
@endpush