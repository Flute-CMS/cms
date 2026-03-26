@props(['user'])

<a class="d-flex align-items-center gap-1" href="{{ url('admin/users/' . $user->id . '/edit') }}"
   hx-boost="true" hx-trigger="click" hx-target="#main" hx-swap="outerHTML transition:true"
   yoyo:ignore="yoyo:ignore" hx-params="not yoyo-id" target="_self" hx-include="none">
    <div class="avatar me-2">
        <img src="{{ asset($user->avatar ?? config('profile.default_avatar')) }}" alt="{{ $user->name }}"
             class="rounded-circle" width="32" height="32" style="object-fit: cover; width: 32px; height: 32px;">
    </div>
    <div class="d-flex flex-column gap-1">
        <span>{!! $user->getDisplayName() !!}</span>
        <small class="text-muted">{{ $user->email }}</small>
    </div>
</a>
