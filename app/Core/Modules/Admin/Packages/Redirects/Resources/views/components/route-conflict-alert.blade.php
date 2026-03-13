@if (!empty($conflictRoute))
    <div class="alert alert-warning">
        <x-icon path="ph.bold.warning-bold" class="alert-icon" />
        <div>
            <strong>@lang('admin-redirects.alert.route_conflict_title')</strong>
            <p class="mb-0 mt-1">{{ $conflictMessage }}</p>
        </div>
    </div>
@endif
