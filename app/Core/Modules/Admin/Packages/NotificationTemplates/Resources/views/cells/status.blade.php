<div class="notification-status">
    @if ($model->is_enabled)
        <span class="badge success">{{ __('admin-notifications.status.active') }}</span>
    @else
        <span class="badge error">{{ __('admin-notifications.status.inactive') }}</span>
    @endif
</div>
