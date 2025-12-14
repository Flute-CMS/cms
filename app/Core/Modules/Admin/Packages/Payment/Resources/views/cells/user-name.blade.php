@props(['user'])

<a href="{{ url('admin/users/' . $user->id . '/edit') }}" class="badge primary m-2" hx-boost="true"
    hx-target="#main" hx-swap="outerHTML transition:true" yoyo:ignore>
    <img src="{{ asset($user->avatar ?? config('profile.default_avatar')) }}" alt="{{ $user->name }}"
        class="rounded-circle me-2" width="20" height="20">
    {{ $user->name }}
</a>
