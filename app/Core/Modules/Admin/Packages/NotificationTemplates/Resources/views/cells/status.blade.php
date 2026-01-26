<div class="notification-status">
    <span class="notification-status__indicator notification-status__indicator--{{ $model->is_enabled ? 'active' : 'inactive' }}"
        data-tooltip="{{ __('admin-notifications.status.' . ($model->is_enabled ? 'active' : 'inactive')) }}"
        data-tooltip-pos="top"></span>
</div>
