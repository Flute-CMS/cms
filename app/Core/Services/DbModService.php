<?php

namespace Flute\Core\Services;

use Flute\Core\Database\Entities\DatabaseConnection;
use Exception;

class DbModService
{
    /**
     * Returns server modes based on provided drivers.
     *
     * @param array|string $drivers
     * @return array
     */
    public function getServerModes($drivers): array
    {
        $modes = $this->fetchModes($drivers);

        return $this->buildResultFromModes($modes, true);
    }

    /**
     * Returns servers based on the provided mode.
     *
     * @param array|string $mode
     * @return array
     */
    public function getModeServers($mode): array
    {
        $modes = $this->fetchModes($mode);

        return $this->buildResultFromModes($modes, false);
    }

    /**
     * Returns a specific server mode.
     *
     * @param string $mode
     * @param int $sid
     * @return mixed
     * @throws Exception
     */
    public function getServerMode(string $mode, int $sid)
    {
        $mode = rep(DatabaseConnection::class)
            ->select()
            ->with('server', ['where' => ['id' => $sid]])
            ->where('mod', $mode)
            ->fetchOne();

        if (!$this->isValidMode($mode)) {
            throw new Exception("Db mode {$mode->dbname} or server {$mode->server} is not exists!");
        }

        return $mode;
    }

    /**
     * Returns the first server associated with a DatabaseConnection.
     * 
     * @param string $mode
     *
     * @return mixed
     * @throws Exception
     */
    public function getFirstServerOfDatabaseConnection( string $mode )
    {
        $databaseConnection = rep(DatabaseConnection::class)
            ->select()
            ->where('mod', $mode)
            ->with('server')
            ->fetchOne();

        if (!$databaseConnection) {
            throw new Exception("No DatabaseConnection entries found.");
        }

        if (empty($databaseConnection->server)) {
            throw new Exception("No server associated with the first DatabaseConnection.");
        }

        return $databaseConnection->server;
    }

    /**
     * Fetches modes based on provided criteria.
     *
     * @param array|string $criteria
     * @return mixed
     */
    private function fetchModes($criteria)
    {
        $modes = rep(DatabaseConnection::class)->select()->with('server');

        if (is_array($criteria)) {
            foreach ($criteria as $driver) {
                $modes = $modes->orWhere('mod', $driver);
            }
        } else {
            $modes->where('mod', $criteria);
        }

        return $modes->fetchAll();
    }

    /**
     * Builds result array from modes.
     *
     * @param $modes
     * @param bool $fullInfo
     * @return array
     */
    private function buildResultFromModes($modes, bool $fullInfo = true): array
    {
        $result = [];

        foreach ($modes as $mode) {
            if (!$this->isValidMode($mode)) {
                continue;
            }

            $serverData = [
                'server' => $mode->server,
                'db' => $mode->dbname
            ];

            if ($fullInfo) {
                $serverData += [
                    'factory' => $mode->mod,
                    'additional' => $mode->additional ? \Nette\Utils\Json::decode($mode->additional) : []
                ];
            }

            $result[$fullInfo ? $mode->server->id : []] = $serverData;
        }

        return $result;
    }

    /**
     * Validates a mode entry.
     *
     * @param $mode
     * @return bool
     */
    private function isValidMode($mode): bool
    {
        return config("database.databases.{$mode->dbname}") && !empty($mode->server);
    }
}
