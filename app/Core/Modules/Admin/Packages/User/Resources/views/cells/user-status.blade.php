@if($user->isOnline())
    <span class="badge success">{{ __('admin-users.status.online') }}</span>
@else
    <span class="badge warning">{{ __('admin-users.status.offline') }}</span>
@endif
