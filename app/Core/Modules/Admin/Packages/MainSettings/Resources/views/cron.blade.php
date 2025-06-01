@php
    // $phpPath = PHP_BINARY;
    $phpPath = 'php';
    $basePath = str_replace(['\\', '..//'], ['/', ''], path());
    $cronCommand = "* * * * * $phpPath $basePath"."flute cron:run >> /dev/null 2>&1";
@endphp

<div class="cron-section">
    <div class="cron-section__header">
        <h5 class="cron-section__title">
            {{ __('admin-main-settings.labels.cron_command') }}
            <x-admin::popover content="{{ __('admin-main-settings.popovers.cron_command') }}" />
        </h5>
    </div>

    <div class="cron-section__command">
        <pre id="cron-command">{{ $cronCommand }}</pre>
    </div>
</div>
