@php
    $phpPath = null;

    if (PHP_SAPI === 'cli' && PHP_BINARY) {
        $phpPath = PHP_BINARY;
    }

    if (!$phpPath && function_exists('shell_exec')) {
        $discoverCommands = ['command -v php 2>/dev/null', 'which php 2>/dev/null'];

        foreach ($discoverCommands as $cmd) {
            $detected = trim((string) @shell_exec($cmd));
            if ($detected && @is_executable($detected)) {
                $phpPath = $detected;
                break;
            }
        }
    }

    if (!$phpPath && defined('PHP_BINDIR')) {
        $bindir = rtrim(PHP_BINDIR, DIRECTORY_SEPARATOR);
        $candidate = $bindir . DIRECTORY_SEPARATOR . (stripos(PHP_OS, 'WIN') === 0 ? 'php.exe' : 'php');
        if (@is_file($candidate) && @is_executable($candidate)) {
            $phpPath = $candidate;
        }
    }

    if (!$phpPath) {
        $fallbacks = [
            '/opt/homebrew/bin/php', // macOS (Apple Silicon) Homebrew
            '/usr/local/bin/php', // common *nix
            '/usr/bin/php',
        ];

        foreach ($fallbacks as $fb) {
            if (@is_executable($fb)) {
                $phpPath = $fb;
                break;
            }
        }
    }

    if (!$phpPath) {
        $phpPath = '/usr/bin/env php';
    }

    $fluteCommand = realpath(BASE_PATH . DIRECTORY_SEPARATOR . 'flute');

    if (!$fluteCommand) {
        $fluteCommand = rtrim(BASE_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'flute';
    }

    $phpIsEnv = $phpPath === '/usr/bin/env php';
    $isReliable = false;

    if (!$phpIsEnv && @is_executable($phpPath) && function_exists('shell_exec')) {
        $sapi = @shell_exec(escapeshellarg($phpPath) . ' -r ' . escapeshellarg('echo PHP_SAPI;'));
        if ($sapi !== null && trim($sapi) === 'cli') {
            $isReliable = true;
        }
    }

    $phpCmdPart = $phpIsEnv ? $phpPath : '"' . $phpPath . '"';

    $cronCommand = "* * * * * $phpCmdPart \"$fluteCommand\" cron:run >> /dev/null 2>&1";
@endphp

<div class="cron-section">
    <div class="cron-section__header">
        <h5 class="cron-section__title">
            {{ __('admin-main-settings.labels.cron_command') }}
            <x-admin::popover content="{{ __('admin-main-settings.popovers.cron_command') }}" />
        </h5>
    </div>

    @if (!$isReliable)
        <x-alert type="warning" onlyBorders withClose=false>
            {{ __('admin-main-settings.messages.cron_cli_warning_text') }}
            <br />{{ __('admin-main-settings.messages.cron_cli_warning_current_label') }}
            <code>{{ $phpPath }}</code>
            <br />{{ __('admin-main-settings.messages.cron_cli_warning_examples_label') }} <code>/usr/bin/php</code>,
            <code>/usr/local/bin/php</code>, <code>/opt/homebrew/bin/php</code>
        </x-alert>
    @endif

    <div class="cron-section__command">
        <pre id="cron-command">{{ $cronCommand }}</pre>
    </div>
</div>
