@php
    $phpPath = PHP_BINARY ?: 'php';
    $fluteCommand = realpath(BASE_PATH . DIRECTORY_SEPARATOR . 'flute');
    
    if (!$fluteCommand) {
        $fluteCommand = rtrim(BASE_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'flute';
    }
    
    $cronCommand = "* * * * * \"$phpPath\" \"$fluteCommand\" cron:run >> /dev/null 2>&1";
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
