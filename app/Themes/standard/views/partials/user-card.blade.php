<div class="user-card-content">
    <div class="user-card-banner">
        <img src="{{ asset($user->banner ?? config('profile.default_banner')) }}" alt="{{ $user->name }}">
    </div>
    @php
        $socialNetworks = $user->socialNetworks;

        $unhiddenSocialNetworks = [];

        foreach ($socialNetworks as $network) {
            if (((bool) $network->hidden !== true || user()->can('admin.users')) && ! empty($network->url)) {
                $unhiddenSocialNetworks[] = $network;
            }
        }
    @endphp
    @if (sizeof($unhiddenSocialNetworks) > 0)
        <div class="user-card-socials">
            @foreach ($unhiddenSocialNetworks as $network)
                <a data-tooltip="{{ $network->socialNetwork->key }}" href="{{ $network->url }}" class="user-card-social"
                    target="_blank">
                    <x-icon path="{{ $network->socialNetwork->icon }}" />
                </a>
            @endforeach
        </div>
    @endif
    <a href="{{ url('profile/'.$user->getUrl()) }}" hx-boost="true" hx-target="#main"
        hx-swap="outerHTML transition:true" class="user-card-avatar" data-tooltip="{{ __('profile.view') }}">
        <img src="{{ asset($user->avatar ?? config('profile.default_avatar')) }}" alt="{{ $user->name }}">
    </a>
    <div class="user-card-roles">
        @php
            $count = 0;
            $roles = $user->roles;
        @endphp
        @if(count($roles) > 2)
            @for($i = 0; $i < count($roles); $i++)
                @if($i >= 2)
                    @break
                @endif

                @php
                    $role = $roles[$i];
                @endphp

                <div class="user-card-role">
                    <span class="user-card-role-square" style="background: {{ $role->color }}"></span>
                    <span class="user-card-role-name">{{ $role->name }}</span>
                </div>
            @endfor
            <div class="d-flex align-center">
                <div class="user-card-role cursor-pointer" data-tooltip="#user_roles_{{ $user->id }}">
                    <span class="user-card-role-name">... {{ count($roles) - 2 }} {{ __('def.roles') }}</span>
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
                                <div class="user-card-role">
                                    <span class="user-card-role-square" style="background: {{ $role->color }}"></span>
                                    <span class="user-card-role-name">{{ $role->name }}</span>
                                </div>
                            </li>
                        @endfor
                    </ul>
                </div>
            </div>
        @else
            @foreach ($roles as $key => $role)
                <div class="user-card-role">
                    <span class="user-card-role-square" style="background: {{ $role->color }}"></span>
                    <span class="user-card-role-name">{{ $role->name }}</span>
                </div>
            @endforeach
        @endif
    </div>
    <div class="user-card-info">
        <h4>{{ $user->name }}</h4>
        @if ($user->isOnline())
            <span class="user-card-online">{{ __('def.online') }}</span>
        @else
            <span class="user-card-offline text-muted">
                {{ $user->getLastLoggedPhrase() }}
            </span>
        @endif
    </div>

    @if (isset($sections['user-card']))
        {!! $sections['user-card'] !!}
    @endif

    <div class="user-card-goto">
        <x-button type="primary" size="small" href="{{ url('profile/'.$user->getUrl()) }}" swap="true">
            {{ __('profile.view') }}
            <x-icon path="ph.regular.arrow-right" />
        </x-button>
    </div>
</div>