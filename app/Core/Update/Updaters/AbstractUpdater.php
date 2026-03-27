<?php

namespace Flute\Core\Update\Updaters;

abstract class AbstractUpdater
{
    /**
     * Get current version
     */
    abstract public function getCurrentVersion(): string;

    /**
     * Get component identifier
     */
    abstract public function getIdentifier(): ?string;

    /**
     * Get component type
     */
    abstract public function getType(): string;

    /**
     * Get component name
     */
    abstract public function getName(): string;

    /**
     * Get component description
     */
    abstract public function getDescription(): string;

    /**
     * Process update
     */
    abstract public function update(array $data): bool;

    protected function enableUpdateMaintenance(): bool
    {
        $basePath = rtrim(str_replace('\\', '/', BASE_PATH), '/') . '/';
        $storageFlag = $basePath . 'storage/app/.maintenance-composer';
        $publicFlag = $basePath . 'public/.maintenance-composer';

        if (is_file($storageFlag) || is_file($publicFlag)) {
            return false;
        }

        @mkdir(dirname($storageFlag), 0o775, true);
        @mkdir(dirname($publicFlag), 0o775, true);

        $payload = [
            'title' => 'Maintenance',
            'message' => 'Update in progress, please try again shortly.',
            'started_at' => date(DATE_ATOM),
            'pid' => getmypid(),
            'force' => false,
        ];

        @file_put_contents($storageFlag, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        @file_put_contents($publicFlag, '1');

        // Safety net: clean up maintenance flags if PHP process dies unexpectedly
        // (OOM, max_execution_time, segfault). The finally block handles normal flow,
        // this covers fatal crashes where finally doesn't execute.
        register_shutdown_function(static function () use ($storageFlag, $publicFlag): void {
            // Only clean up if there was an error (normal exit is handled by finally/disableUpdateMaintenance)
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE], true)) {
                @unlink($storageFlag);
                @unlink($publicFlag);
            }
        });

        return true;
    }

    protected function disableUpdateMaintenance(bool $enabledByThisCall): void
    {
        if (!$enabledByThisCall) {
            return;
        }

        $basePath = rtrim(str_replace('\\', '/', BASE_PATH), '/') . '/';
        $storageFlag = $basePath . 'storage/app/.maintenance-composer';
        $publicFlag = $basePath . 'public/.maintenance-composer';

        $payload = [];
        if (is_file($storageFlag)) {
            $raw = @file_get_contents($storageFlag);
            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $payload = $decoded;
                }
            }
        }

        if (!empty($payload['force'])) {
            return;
        }

        @unlink($storageFlag);
        @unlink($publicFlag);
    }

    /**
     * Safely change file owner if supported by the runtime
     */
    protected function safeChown(string $path, $user): void
    {
        if (function_exists('chown')) {
            @chown($path, $user);
        }
    }

    /**
     * Safely change file group if supported by the runtime
     */
    protected function safeChgrp(string $path, $group): void
    {
        if (function_exists('chgrp')) {
            @chgrp($path, $group);
        }
    }
}
