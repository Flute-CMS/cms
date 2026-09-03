<?php

namespace Flute\Core\Database\Concerns;

use Cycle\Schema\Exception\SyncException;
use Flute\Core\Database\DatabaseCapabilities;
use Throwable;

trait HandlesSyncDiagnostics
{
    protected function logSyncError(SyncException $e): void
    {
        $message = $e->getMessage();
        $previous = $e->getPrevious();
        $rootMessage = $previous ? $previous->getMessage() : $message;

        $context = [
            'exception' => get_class($e),
            'message' => $message,
        ];

        if ($previous) {
            $context['root_exception'] = get_class($previous);
            $context['root_message'] = $rootMessage;
        }

        $diagnostic = $this->diagnoseSyncError($rootMessage);
        if ($diagnostic !== null) {
            $context['diagnostic'] = $diagnostic;
        }

        try {
            $driver = $this->dbal->database()->getDriver();
            $pdo = null;
            if (method_exists($driver, 'getPDO')) {
                $pdo = $driver->getPDO();
            } elseif (method_exists($driver, 'getConnection')) {
                $conn = $driver->getConnection();
                if ($conn instanceof \PDO) {
                    $pdo = $conn;
                }
            }

            if ($pdo) {
                $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
                $context['db_version'] = $version;

                $caps = DatabaseCapabilities::fromPdo($pdo);
                $context['db_server'] = $caps->getServerLabel();
                $context['db_clean_version'] = $caps->getCleanVersion();
                $context['meets_minimum'] = $caps->meetsMinimumVersion();
                $context['supports_datetime_defaults'] = $caps->supportsDatetimeDefaults();
                $context['supports_json'] = $caps->supportsJsonType();
            }
        } catch (Throwable) {
        }

        $level = $diagnostic !== null ? 'error' : 'warning';
        logs('database')->{$level}(
            'Schema sync failed: '
            . $message
            . ( $diagnostic ? ' [Diagnostic: ' . $diagnostic . ']' : '' )
            . ' — retrying without SyncTables',
            $context,
        );
    }

    protected function diagnoseSyncError(string $message): ?string
    {
        $msg = strtolower($message);

        if (str_contains($msg, '1067') && str_contains($msg, 'invalid default value')) {
            if (preg_match('/[\'"]([a-z_]+)[\'"]/i', $message, $m)) {
                $column = $m[1];
            } else {
                $column = '(unknown column)';
            }

            return sprintf(
                'Column "%s" — DATETIME column cannot have DEFAULT CURRENT_TIMESTAMP. '
                . 'This usually means MySQL < 5.6.5 or MariaDB < 10.0.1. '
                . 'Upgrade your database server or check the sql_mode setting.',
                $column,
            );
        }

        if (str_contains($msg, '1071') && str_contains($msg, 'specified key was too long')) {
            return (
                'Index key too long — this is common with utf8mb4 on MySQL < 5.7.7. '
                . 'Consider upgrading MySQL or using innodb_large_prefix=ON with ROW_FORMAT=DYNAMIC.'
            );
        }

        if (str_contains($msg, 'syntax error') && str_contains($msg, 'json')) {
            return (
                'JSON column type not supported — requires MySQL 5.7.8+ or MariaDB 10.2.7+. '
                . 'Upgrade your database server.'
            );
        }

        if (str_contains($msg, '1452') && str_contains($msg, 'foreign key constraint fails')) {
            return (
                'Foreign key constraint violation during schema sync. '
                . 'There may be orphaned rows in a table that prevent adding a foreign key. '
                . 'Check data integrity or temporarily disable foreign_key_checks.'
            );
        }

        if (str_contains($msg, 'type text/blob/json can not have non empty default value')) {
            return (
                'A TEXT/BLOB/JSON column has a non-empty default value. '
                . 'MySQL does not allow DEFAULT on these types. '
                . 'The column definition in the entity needs to be changed.'
            );
        }

        if (str_contains($msg, '1146') || str_contains($msg, 'table') && str_contains($msg, 'doesn\'t exist')) {
            return 'A referenced table does not exist. Run migrations first or check module dependencies.';
        }

        return null;
    }
}
