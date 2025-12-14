<?php

namespace Flute\Core\Services;

use Cycle\Database\Injection\Parameter;
use Exception;
use Flute\Core\Database\Entities\DatabaseConnection;
use Flute\Core\Database\Entities\Server;

class DatabaseService
{
    public array $cachedConnections;

    /**
     * Retrieves server modes based on provided mods.
     *
     * @param string|array<string> $mods
     * @return array<int, array<string, mixed>>
     */
    public function getServerModes(string|array $mods): array
    {
        $modes = $this->fetchModes($mods);

        return $this->formatModes($modes, true);
    }

    /**
     * Retrieves servers based on the provided mode.
     *
     * @param string|array<string> $mod
     * @return array<int, array<string, mixed>>
     */
    public function getServersByMode(string|array $mod): array
    {
        $modes = $this->fetchModes($mod);

        return $this->formatModes($modes, false);
    }

    /**
     * Retrieves a specific server mode by mode and server ID.
     *
     * @throws Exception
     */
    public function getServerMode(string $mod, int $serverId): DatabaseConnection
    {
        $mode = DatabaseConnection::query()
            ->with('server', ['where' => ['id' => $serverId]])
            ->where('mod', $mod)
            ->fetchOne();

        if (!$this->isValidMode($mode)) {
            throw new Exception("Database mode '{$mode->dbname}' or server '{$mode->server->name}' does not exist.");
        }

        return $mode;
    }

    /**
     * Retrieves the primary database connection based on modes.
     *
     * @param string|array<string> $mods
     * @throws Exception
     * @return array<string, mixed>
     */
    public function getPrimaryConnection(string|array $mods = []): array
    {
        $databaseConnection = DatabaseConnection::query()
            ->with('server');

        if (is_array($mods)) {
            $databaseConnection->where('mod', 'IN', new Parameter($mods));
        } else {
            $databaseConnection->where('mod', $mods);
        }

        $databaseConnection = $databaseConnection->fetchOne();

        if (!$databaseConnection) {
            throw new Exception("No DatabaseConnection entries found for the specified mods.");
        }

        if (!$databaseConnection->server) {
            throw new Exception("No server associated with the primary DatabaseConnection.");
        }

        return [
            'server' => $databaseConnection->server,
            'connection' => $databaseConnection,
        ];
    }

    /**
     * Retrieves connection information for the specified server ID within the given mods.
     *
     * @param string|array<string> $mods
     * @throws Exception
     * @return array<string, mixed>|null
     */
    public function getConnectionInfoByServerId(int $serverId, string|array $mods): ?array
    {
        $mods = is_array($mods) ? $mods : [$mods];

        $connection = DatabaseConnection::query()
            ->with('server', ['where' => ['id' => $serverId]])
            ->where('mod', 'IN', new Parameter($mods))
            ->fetchOne();

        if ($connection && $this->isValidMode($connection)) {
            return [
                'server' => $connection->server,
                'connection' => $connection,
            ];
        }

        return null;
    }

    /**
     * Finds the first server by mode.
     */
    public function findFirstServerByMode(string $mod): ?Server
    {
        $databaseConnection = DatabaseConnection::query()
            ->where('mod', $mod)
            ->with('server')
            ->fetchOne();

        return $databaseConnection->server ?? null;
    }

    /**
     * Fetches modes based on provided criteria.
     *
     * @param string|array<string> $criteria
     * @return array<int, DatabaseConnection>
     */
    private function fetchModes(string|array $criteria): array
    {
        $mods = is_array($criteria) ? $criteria : [$criteria];

        $query = DatabaseConnection::query()->with('server');
        if ($mods) {
            $query->where('mod', 'IN', new Parameter($mods));
        }

        return $query->fetchAll();
    }

    /**
     * Formats modes into a structured array.
     *
     * @param array<int, DatabaseConnection> $modes
     * @return array<int, array<string, mixed>>
     */
    private function formatModes(array $modes, bool $includeAdditionalInfo = true): array
    {
        $result = [];

        foreach ($modes as $mode) {
            if (!$this->isValidMode($mode)) {
                continue;
            }

            $serverData = [
                'server' => $mode->server,
                'dbname' => $mode->dbname,
            ];

            if ($includeAdditionalInfo) {
                $serverData['mod'] = $mode->mod;
                $serverData['additional'] = $mode->additional ? json_decode($mode->additional, true) : [];
            }

            $result[$includeAdditionalInfo ? $mode->server->id : count($result)] = $serverData;
        }

        return $result;
    }

    /**
     * Validates a mode entry.
     */
    private function isValidMode(?DatabaseConnection $mode): bool
    {
        return $mode !== null && config("database.databases.{$mode->dbname}") && $mode->server;
    }

    private function getAllConnections(): array
    {
        if (isset($this->cachedConnections)) {
            return $this->cachedConnections;
        }

        return $this->cachedConnections = DatabaseConnection::query()->with('server')->fetchAll();
    }
}
