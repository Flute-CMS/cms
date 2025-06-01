@if($visible)
    <span class="badge success">{{ __('admin-users.status.visible') }}</span>
@else
    <span class="badge warning">{{ __('admin-users.status.hidden') }}</span>
@endif 