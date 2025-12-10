<a class="d-flex align-items-center gap-1" href="{{ url('admin/users/' . $user->id . '/edit') }}" hx-boost="true"
    hx-trigger="click" hx-target="#main" hx-swap="outerHTML transition:true"
    yoyo:ignore="yoyo:ignore" hx-params="not yoyo-id" target="_self" hx-include="none">
    <div class="avatar me-2">
        <img src="{{ asset($user->avatar ?? config('profile.default_avatar')) }}" alt="{{ $user->name }}"
            class="rounded-circle" width="40" height="40" style="object-fit: cover; width: 40px; height: 40px;">
    </div>
    <div class="d-flex flex-column gap-1">
        <span>
            {{ $user->name }}
        </span>
        <div class="d-flex align-items-center gap-1">
            @if ($user->verified)
                <span class="badge success">{{ __('admin-users.status.verified') }}</span>
            @endif

            @if ($user->hidden)
                <span class="badge warning">{{ __('admin-users.status.hidden') }}</span>
            @endif

            @if ($user->isBlocked())
                <span class="badge danger">{{ __('admin-users.status.blocked') }}</span>
            @endif
        </div>
        <small class="text-muted">{{ $user->email }}</small>
    </div>
</a>