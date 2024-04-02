@extends(tt('layout.blade.php'))

@push('header')
    @at(tt('assets/styles/pages/_profile.scss'))
@endpush

@section('title')
    {{ !empty(page()->title) ? page()->title : __('def.profile_user', ['name' => $user->name]) }}
@endsection

@push('profile_tabs')
    @if (sizeof($tabs) > 0)
        <a href='{{ url('profile/' . $user->getUrl()) }}' class="@if (!request()->input('tab')) active @endif">
            <i class="ph ph-house"></i>
            {{ __('def.main') }}
        </a>

        @foreach ($tabs as $key => $tab)
            <a href='{{ url('profile/' . $user->getUrl(), [
                'tab' => $key,
            ]) }}'
                class="@if ($active === $key) active @endif {{ $tab['classes'] }}">
                <i class="{{ $tab['icon'] }}"></i>
                {{ __($tab['name']) }}
            </a>
        @endforeach
    @endif
@endpush

@push('profile_container')
    @if (!profile()->isMainDisabled())
        <div class="profile_banner" style="background: url({{ url($user->banner) }}) center center / cover no-repeat;">

            @if (user()->id === $user->id)
                <input type="file" id="banner-input" style="display: none;"
                    accept="{{ implode(', ', config('profile.banner_types')) }}">

                <div data-tooltip="@t('profile.change_banner')" data-tooltip-conf="left" id="profile_banner_change">
                    <i class="ph ph-pencil-simple"></i>
                </div>
            @endif

            <!-- Add a progress bar for banner -->
            <div class="progress-container">
                <div class="progress-bar"></div>
            </div>
        </div>

        <div class="profile_info">
            @if (user()->id === $user->id)
                <input type="file" id="avatar-input" style="display: none;"
                    accept="{{ implode(', ', config('profile.avatar_types')) }}">
            @endif
            <!-- Add a progress bar as border for avatar -->
            <div class="avatar-border">
                <div class="avatar-loading-indicator"></div>
            </div>

            <div @if (user()->id === $user->id) class="profile_avatar_wrapper editable" data-tooltip="@t('profile.change_avatar')" data-tooltip-conf="top" id="profile_avatar_change" @else class="profile_avatar_wrapper" @endif>
                <img src="{{ url($user->avatar) }}" alt="{{ $user->name }}" class="profile_avatar">
                @if (user()->id === $user->id)
                    <i class="ph ph-pencil-simple profile_change_ico"></i>
                @endif
            </div>

            <div class="profile_up_info">
                <div class="profile_up_name">
                    <h2>{{ $user->name }}</h2>
                    @if (user()->hasPermission('admin.users') || $user->id == user()->id)
                        <p>@t('def.balance'): {{ $user->balance . app('lk.currency_view') }}</p>
                    @endif
                </div>
                @if (sizeof($user->socialNetworks) > 0)
                    <div class="profile_up_socials">
                        @foreach ($user->socialNetworks as $network)
                            @if ((bool) $network->hidden !== true || user()->hasPermission('admin.users'))
                                <a href="{{ $network->url }}" target="_blank">
                                    <div data-tooltip="{{ $network->socialNetwork->key }}" data-tooltip-conf="top">
                                        {!! $network->socialNetwork->icon !!}
                                    </div>
                                </a>
                            @endif
                        @endforeach
                        @stack('profile_socials')
                    </div>
                @endif
            </div>
            <div class="profile_background_info">
                @if (sizeof($user->roles))
                    <div class="profile_user_roles">
                        @foreach ($user->roles as $role)
                            <div class="profile_user_role">
                                <div class="profile_user_role_square" style="background: {{ $role->color }}">
                                </div>
                                <div class="profile_user_role_name" style="color: {{ $role->color }}">
                                    {{ $role->name }}
                                </div>
                            </div>
                        @endforeach
                        @stack('profile_roles')
                    </div>
                @endif
                @if (user()->hasPermission('admin.users') || $user->id === user()->id)
                    <a role="button" class="btn profile_edit_btn outline"
                        href="{{  url($user->id !== user()->id ? 'admin/users/edit/' . $user->id : 'profile/edit') }}">
                        @t('def.edit')
                    </a>
                @endif
            </div>
        </div>
    @endif
@endpush

@push('content')
    @navbar
    <div class="container">
        @navigation
        @breadcrumb()
        @flash
        @editor

        @stack('container')

        <div class="row gx-3">
            @if (sizeof($tabs) > 0)
                <div class="col-md-3">
                    <div class="profile_tabs">
                        @stack('profile_tabs')
                    </div>
                </div>
            @endif
            <div class="col-md-{{ !sizeof($tabs) ? 12 : 9 }}">
                @if (!profile()->isMainDisabled())
                    <div class="profile_container mb-3">
                        @stack('profile_container')
                    </div>
                @endif
                <div class="profile_body">
                    @stack('profile_body')
                </div>
            </div>
        </div>
    </div>
@endpush

@push('footer')
    @at(tt('assets/js/pages/profile.js'))
@endpush

@footer
