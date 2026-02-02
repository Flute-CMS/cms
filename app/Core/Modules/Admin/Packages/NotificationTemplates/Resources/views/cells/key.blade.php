<div class="notification-key">
    <div class="notification-key__code">
        <code>{{ $model->key }}</code>
        @if ($model->is_customized)
            <span class="notification-key__customized" data-tooltip="{{ __('admin-notifications.customized') }}"
                data-tooltip-pos="top">
                <x-icon path="ph.bold.pencil-simple-bold" />
            </span>
        @endif
    </div>
    <span class="badge {{ ($model->module ?? 'core') === 'core' ? 'primary' : 'accent' }} notification-key__module">
        {{ $model->module ?? 'core' }}
    </span>
</div>
