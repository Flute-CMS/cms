@php
    $roles = $user->roles;
    $primaryRole = $roles[0] ?? null;
    $roleColor = $primaryRole?->color ?? '#8e8e8e';
    $socialNetworks = $user->socialNetworks;
    $unhiddenSocialNetworks = [];
    foreach ($socialNetworks as $network) {
        if (((bool) $network->hidden !== true || user()->can('admin.users') || user()->can('admin.users.view')) && !empty($network->url)) {
            $unhiddenSocialNetworks[] = $network;
        }
    }
@endphp

<div class="user-card-content enhanced" style="--role-color: {{ $roleColor }}">
    <div class="user-card-header">
        <div class="user-card-banner">
            <img src="{{ asset($user->banner ?? config('profile.default_banner')) }}" alt="{{ $user->name }}" loading="lazy" decoding="async">
        </div>

        <a href="{{ url('profile/' . $user->getUrl()) }}" hx-boost="true" hx-target="#main"
           hx-swap="outerHTML transition:true" class="user-card-avatar" data-tooltip="{{ __('profile.view') }}">
            <img src="{{ asset($user->avatar ?? config('profile.default_avatar')) }}" alt="{{ $user->name }}" loading="lazy" decoding="async">
            <span class="uc-status-dot {{ $user->isOnline() ? 'online' : 'offline' }}"
                  aria-label="{{ $user->isOnline() ? __('def.online') : __('def.offline') }}"
                  data-tooltip="{{ $user->isOnline() ? __('def.online') : __('def.offline') }}"></span>
        </a>
    </div>

    <div class="user-card-body">
        <div class="user-card-identity">
            <div class="user-card-name-row">
                <h4 class="user-card-name" style="color: {{ $roleColor }}">{!! $user->getDisplayName(withColor: false) !!}</h4>
                @if ($user->approved)
                    <span class="verified-badge verified-badge--small" data-tooltip="{{ __('def.approved') }}">
                        <x-icon path="ph.bold.seal-check-bold" />
                    </span>
                @elseif (user()->can('admin.boss'))
                    <span class="verified-badge verified-badge--small verified-badge--ghosted" data-tooltip="{{ __('profile.admin_actions.approve_user') }}">
                        <x-icon path="ph.bold.seal-check-bold" />
                    </span>
                @endif
            </div>

            <div class="user-card-presence">
                <span class="uc-presence-dot {{ $user->isOnline() ? 'online' : '' }}"></span>
                <span>{{ $user->isOnline() ? __('def.online') : $user->getLastLoggedPhrase() }}</span>
            </div>
        </div>

        @if (count($roles) > 0)
            @php
                $maxVisible = 5;
                $visibleRoles = array_slice($roles, 0, $maxVisible);
            @endphp

            <div class="uc-roles role-badges role-badges--center">
                @foreach ($visibleRoles as $role)
                    <x-role-badge :role="$role" />
                @endforeach

                @if (count($roles) > $maxVisible)
                    <span class="role-badges__overflow" data-tooltip="#uc_roles_{{ $user->id }}">
                        +{{ count($roles) - $maxVisible }}
                    </span>
                    <div id="uc_roles_{{ $user->id }}" class="d-none">
                        <ul class="user-roles-list">
                            @for ($i = $maxVisible; $i < count($roles); $i++)
                                @php $role = $roles[$i]; @endphp
                                <li>
                                    <div class="user-card-role">
                                        <span class="user-card-role-square"
                                              style="background: {{ $role->color }}"></span>
                                        <span class="user-card-role-name">{{ $role->name }}</span>
                                    </div>
                                </li>
                            @endfor
                        </ul>
                    </div>
                @endif
            </div>
        @endif

        @if (isset($sections['user-card']))
            {!! $sections['user-card'] !!}
        @endif

        <div class="uc-card-bottom">
            @if (sizeof($unhiddenSocialNetworks) > 0)
                <div class="uc-socials">
                    @foreach ($unhiddenSocialNetworks as $network)
                        <a data-tooltip="{{ $network->socialNetwork?->key }}" href="{{ $network->url }}"
                           class="uc-social-link" target="_blank" rel="noopener">
                            <x-icon path="{{ $network->socialNetwork?->icon }}" />
                        </a>
                    @endforeach
                </div>
            @endif

            <a href="{{ url('profile/' . $user->getUrl()) }}" hx-boost="true" hx-target="#main"
               hx-swap="outerHTML transition:true" class="uc-profile-btn">
                <span class="uc-profile-btn__label">{{ __('profile.view') }}</span>
                <x-icon path="ph.bold.arrow-right-bold" />
            </a>
        </div>
    </div>
</div>
