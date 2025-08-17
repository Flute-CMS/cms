@php
    $roles = $user->roles;
    $primaryRole = $roles[0] ?? null;
    $roleColor = $primaryRole?->color ?? '#8e8e8e';
    $socialNetworks = $user->socialNetworks;
    $unhiddenSocialNetworks = [];
    foreach ($socialNetworks as $network) {
        if (((bool) $network->hidden !== true || user()->can('admin.users')) && !empty($network->url)) {
            $unhiddenSocialNetworks[] = $network;
        }
    }
@endphp

<div class="user-card-content enhanced" style="--role-color: {{ $roleColor }}">
    <div class="user-card-header">
        <div class="user-card-banner">
            <img src="{{ asset($user->banner ?? config('profile.default_banner')) }}" alt="{{ $user->name }}">
        </div>

        @if (sizeof($unhiddenSocialNetworks) > 0)
            <div class="user-card-actions">
                @foreach ($unhiddenSocialNetworks as $network)
                    <a data-tooltip="{{ $network->socialNetwork->key }}" href="{{ $network->url }}" class="user-card-social" target="_blank">
                        <x-icon path="{{ $network->socialNetwork->icon }}" />
                    </a>
                @endforeach
            </div>
        @endif

        <a href="{{ url('profile/' . $user->getUrl()) }}" hx-boost="true" hx-target="#main"
           hx-swap="outerHTML transition:true" class="user-card-avatar" data-tooltip="{{ __('profile.view') }}">
            <img src="{{ asset($user->avatar ?? config('profile.default_avatar')) }}" alt="{{ $user->name }}">
            <span class="uc-status-dot {{ $user->isOnline() ? 'online' : 'offline' }}"
                  aria-label="{{ $user->isOnline() ? __('def.online') : __('def.offline') }}"
                  data-tooltip="{{ $user->isOnline() ? __('def.online') : __('def.offline') }}"></span>
        </a>
    </div>

    <div class="user-card-body">
        <div class="user-card-info">
            <h4 class="user-card-name" style="color: {{ $roleColor }}">{{ $user->name }}</h4>
            @if (!$user->isOnline())
                <span class="user-card-offline text-muted">{{ $user->getLastLoggedPhrase() }}</span>
            @endif
        </div>

        @if (count($roles) > 0)
            <div class="user-card-roles">
                @php $first = $roles[0]; @endphp
                <div class="role-pill" style="--pill-color: {{ $first->color }}">
                    <span class="role-swatch" style="background: {{ $first->color }}"></span>
                    <span class="role-name">{{ $first->name }}</span>
                </div>

                @if (count($roles) > 1)
                    <div class="roles-extra">
                        @php $displayExtra = min(count($roles) - 1, 4); @endphp
                        @for ($i = 1; $i <= $displayExtra; $i++)
                            @php $role = $roles[$i]; @endphp
                            <span class="role-dot" style="--dot-color: {{ $role->color }}" data-tooltip="{{ $role->name }}"></span>
                        @endfor

                        @if (count($roles) - 1 > 4)
                            @php $remaining = count($roles) - 1 - 4; @endphp
                            <span class="role-dot more" data-tooltip="#user_roles_{{ $user->id }}">+{{ $remaining }}</span>
                            <div id="user_roles_{{ $user->id }}" class="d-none">
                                <ul class="user-roles-list">
                                    @for ($a = 1; $a < count($roles); $a++)
                                        @php $role = $roles[$a]; @endphp
                                        <li>
                                            <div class="user-card-role">
                                                <span class="user-card-role-square" style="background: {{ $role->color }}"></span>
                                                <span class="user-card-role-name">{{ $role->name }}</span>
                                            </div>
                                        </li>
                                    @endfor
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @endif

        @if (isset($sections['user-card']))
            {!! $sections['user-card'] !!}
        @endif

        <div class="user-card-goto">
            <x-button type="primary" size="small" href="{{ url('profile/' . $user->getUrl()) }}" swap="true">
                {{ __('profile.view') }}
                <x-icon path="ph.regular.arrow-right" />
            </x-button>
        </div>
    </div>
</div>
