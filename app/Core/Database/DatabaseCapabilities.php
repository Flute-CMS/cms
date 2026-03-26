<?php

namespace Flute\Core\Database;

use PDO;

class DatabaseCapabilities
{
    public const MIN_MYSQL_VERSION = '5.7.0';
    public const MIN_MARIADB_VERSION = '10.2.0';
    public const MIN_PGSQL_VERSION = '10.0';

    public const RECOMMENDED_MYSQL_VERSION = '8.0.0';
    public const RECOMMENDED_MARIADB_VERSION = '10.6.0';

    private string $rawVersion;
    private string $cleanVersion;
    private string $driver;
    private bool $isMariaDb;

    /** @var array<string, bool> */
    private array $capabilities = [];

    public function __construct(PDO $pdo, string $driver = 'mysql')
    {
        $this->driver = $driver;
        $this->rawVersion = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
        $this->isMariaDb = stripos($this->rawVersion, 'mariadb') !== false;
        $this->cleanVersion = $this->parseVersion($this->rawVersion);
        $this->detect();
    }

    public static function fromPdo(PDO $pdo, string $driver = 'mysql'): self
    {
        return new self($pdo, $driver);
    }

    public function getRawVersion(): string
    {
        return $this->rawVersion;
    }

    public function getCleanVersion(): string
    {
        return $this->cleanVersion;
    }

    public function isMariaDb(): bool
    {
        return $this->isMariaDb;
    }

    public function getServerLabel(): string
    {
        if ($this->driver === 'pgsql') {
            return 'PostgreSQL';
        }

        return $this->isMariaDb ? 'MariaDB' : 'MySQL';
    }

    public function meetsMinimumVersion(): bool
    {
        if ($this->driver === 'pgsql') {
            return version_compare($this->cleanVersion, self::MIN_PGSQL_VERSION, '>=');
        }

        $minVersion = $this->isMariaDb ? self::MIN_MARIADB_VERSION : self::MIN_MYSQL_VERSION;

        return version_compare($this->cleanVersion, $minVersion, '>=');
    }

    public function getMinimumVersion(): string
    {
        if ($this->driver === 'pgsql') {
            return self::MIN_PGSQL_VERSION;
        }

        return $this->isMariaDb ? self::MIN_MARIADB_VERSION : self::MIN_MYSQL_VERSION;
    }

    public function getRecommendedVersion(): string
    {
        if ($this->driver === 'pgsql') {
            return self::MIN_PGSQL_VERSION;
        }

        return $this->isMariaDb ? self::RECOMMENDED_MARIADB_VERSION : self::RECOMMENDED_MYSQL_VERSION;
    }

    public function meetsRecommendedVersion(): bool
    {
        if ($this->driver === 'pgsql') {
            return true;
        }

        $recommended = $this->isMariaDb ? self::RECOMMENDED_MARIADB_VERSION : self::RECOMMENDED_MYSQL_VERSION;

        return version_compare($this->cleanVersion, $recommended, '>=');
    }

    public function supportsDatetimeDefaults(): bool
    {
        return $this->capabilities['datetime_defaults'] ?? true;
    }

    public function supportsJsonType(): bool
    {
        return $this->capabilities['json_type'] ?? true;
    }

    public function supportsUtf8mb4(): bool
    {
        return $this->capabilities['utf8mb4'] ?? true;
    }

    public function supportsWindowFunctions(): bool
    {
        return $this->capabilities['window_functions'] ?? true;
    }

    public function supportsCte(): bool
    {
        return $this->capabilities['cte'] ?? true;
    }

    /**
     * @return array<string, array{supported: bool, description: string}>
     */
    public function getWarnings(): array
    {
        $warnings = [];

        if (!$this->meetsMinimumVersion()) {
            $warnings['version_too_old'] = [
                'supported' => false,
                'description' => sprintf(
                    '%s %s is below the minimum required version %s',
                    $this->getServerLabel(),
                    $this->cleanVersion,
                    $this->getMinimumVersion(),
                ),
            ];
        }

        if (!$this->supportsDatetimeDefaults()) {
            $warnings['no_datetime_defaults'] = [
                'supported' => false,
                'description' => sprintf(
                    '%s %s does not support DEFAULT CURRENT_TIMESTAMP on DATETIME columns (requires MySQL 5.6.5+ / MariaDB 10.0.1+)',
                    $this->getServerLabel(),
                    $this->cleanVersion,
                ),
            ];
        }

        if (!$this->supportsJsonType()) {
            $warnings['no_json'] = [
                'supported' => false,
                'description' => sprintf(
                    '%s %s does not support native JSON columns (requires MySQL 5.7.8+ / MariaDB 10.2.7+)',
                    $this->getServerLabel(),
                    $this->cleanVersion,
                ),
            ];
        }

        if (!$this->supportsUtf8mb4()) {
            $warnings['no_utf8mb4'] = [
                'supported' => false,
                'description' => sprintf(
                    '%s %s does not fully support utf8mb4 charset (requires MySQL 5.5.3+)',
                    $this->getServerLabel(),
                    $this->cleanVersion,
                ),
            ];
        }

        if ($this->meetsMinimumVersion() && !$this->meetsRecommendedVersion()) {
            $warnings['version_not_recommended'] = [
                'supported' => true,
                'description' => sprintf(
                    '%s %s is supported but %s+ is recommended for best performance and stability',
                    $this->getServerLabel(),
                    $this->cleanVersion,
                    $this->getRecommendedVersion(),
                ),
            ];
        }

        return $warnings;
    }

    /**
     * @return array<string, bool>
     */
    public function toArray(): array
    {
        return [
            'driver' => $this->driver,
            'server' => $this->getServerLabel(),
            'version' => $this->cleanVersion,
            'raw_version' => $this->rawVersion,
            'is_mariadb' => $this->isMariaDb,
            'meets_minimum' => $this->meetsMinimumVersion(),
            'meets_recommended' => $this->meetsRecommendedVersion(),
            'datetime_defaults' => $this->supportsDatetimeDefaults(),
            'json_type' => $this->supportsJsonType(),
            'utf8mb4' => $this->supportsUtf8mb4(),
            'window_functions' => $this->supportsWindowFunctions(),
            'cte' => $this->supportsCte(),
        ];
    }

    private function parseVersion(string $raw): string
    {
        if (preg_match('/(\d+\.\d+\.\d+)/', $raw, $matches)) {
            return $matches[1];
        }

        if (preg_match('/(\d+\.\d+)/', $raw, $matches)) {
            return $matches[1] . '.0';
        }

        return '0.0.0';
    }

    private function detect(): void
    {
        if ($this->driver === 'pgsql') {
            $this->capabilities = [
                'datetime_defaults' => true,
                'json_type' => version_compare($this->cleanVersion, '9.2.0', '>='),
                'utf8mb4' => true,
                'window_functions' => version_compare($this->cleanVersion, '8.4.0', '>='),
                'cte' => version_compare($this->cleanVersion, '8.4.0', '>='),
            ];

            return;
        }

        if ($this->driver === 'sqlite') {
            $this->capabilities = [
                'datetime_defaults' => true,
                'json_type' => true,
                'utf8mb4' => true,
                'window_functions' => true,
                'cte' => true,
            ];

            return;
        }

        if ($this->isMariaDb) {
            $this->capabilities = [
                'datetime_defaults' => version_compare($this->cleanVersion, '10.0.1', '>='),
                'json_type' => version_compare($this->cleanVersion, '10.2.7', '>='),
                'utf8mb4' => version_compare($this->cleanVersion, '5.5.0', '>='),
                'window_functions' => version_compare($this->cleanVersion, '10.2.0', '>='),
                'cte' => version_compare($this->cleanVersion, '10.2.1', '>='),
            ];

            return;
        }

        // MySQL
        $this->capabilities = [
            'datetime_defaults' => version_compare($this->cleanVersion, '5.6.5', '>='),
            'json_type' => version_compare($this->cleanVersion, '5.7.8', '>='),
            'utf8mb4' => version_compare($this->cleanVersion, '5.5.3', '>='),
            'window_functions' => version_compare($this->cleanVersion, '8.0.0', '>='),
            'cte' => version_compare($this->cleanVersion, '8.0.0', '>='),
        ];
    }
}
