<x-card class="users-stats">
    <x-slot name="header">
        <div class="users-stats-header">
            <h5>{{ __('widgets.users_new') }}</h5>
            <small class="text-muted">{{ count($users) }}</small>
        </div>
    </x-slot>
    <div class="users-stats-content" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true">
        @if (!empty($users))
            @if (($display_type ?? 'text') == 'avatar')
                <div class="users-stats-avatar-mode">
                    @foreach ($users as $user)
                        <x-link href="{{ url('profile/' . $user->getUrl()) }}" data-user-card class="user-avatar" title="{{ $user->name }}">
                            <img src="{{ asset($user->avatar) }}" alt="{{ $user->name }}" data-tooltip="{{ $user->name }}">
                        </x-link>
                    @endforeach
                </div>
            @else
                @foreach ($users as $key => $user)
                    <x-link href="{{ url('profile/' . $user->getUrl()) }}" data-user-card class="users-stats-name">
                        {{ $user->name }}
                    </x-link>
                    @isset($users[$key + 1])
                        ,
                    @endif
                @endforeach
            @endif
        @else
            <div class="users-stats-empty">
                <x-icon path="ph.regular.smiley-sad" />
            </div>
        @endif
    </div>
</x-card>
