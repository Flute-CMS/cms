<div class="d-flex flex-column gap-1">
    <div class="d-flex align-items-center gap-2">
        <code class="fw-semibold">{{ $model->key }}</code>
        @if ($model->is_customized)
            <span class="badge warning ms-2">{{ __('admin-notifications.customized') }}</span>
        @endif
    </div>
    @php
        $module = $model->module ?? 'core';
        $moduleClass = $module === 'core' ? 'primary' : 'secondary';
    @endphp
    <span class="badge {{ $moduleClass }}">{{ $module }}</span>
</div>
