<div class="d-flex items-center gap-2">
    <span class="badge {{ $social->enabled ? 'success' : 'error' }}" title="{{ __('admin-social.fields.enabled.label') }}">
        {{ $social->enabled ? __('admin-social.status.active') : __('admin-social.status.inactive') }}
    </span>
</div>
