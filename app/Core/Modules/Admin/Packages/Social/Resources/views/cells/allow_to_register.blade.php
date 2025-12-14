<div class="d-flex items-center gap-2">
    <span class="badge {{ $social->allowToRegister ? 'success' : 'error' }}" title="{{ __('admin-social.table.allow_register') }}">
        {{ $social->allowToRegister ? __('admin-social.status.active') : __('admin-social.status.inactive') }}
    </span>
</div>
