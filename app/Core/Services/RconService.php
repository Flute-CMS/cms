<?php

namespace Flute\Core\Services;

use Exception;
use Flute\Core\Database\Entities\Server;
use xPaw\SourceQuery\SourceQuery;

class RconService
{
    private const GOLDSOURCE_MODS = ['10', 'all_hl_games_mods'];

    public function execute(Server $server, string $command, int $timeout = 3): string
    {
        if (empty($server->rcon)) {
            throw new Exception("RCON password is not configured for server #{$server->id}");
        }

        $query = new SourceQuery();

        try {
            $port = $this->getRconPort($server);
            $engine = $this->getEngineType($server->mod);

            $query->Connect($server->ip, $port, $timeout, $engine);
            $query->SetRconPassword($server->rcon);

            $result = $query->Rcon($command);

            return $result ?: '';
        } finally {
            $query->Disconnect();
        }
    }

    public function isAvailable(Server $server): bool
    {
        return !empty($server->rcon);
    }

    /**
     * @return array<int, array{name: string, score: int, duration: float}>
     */
    public function getPlayerList(Server $server, int $timeout = 3): array
    {
        $query = new SourceQuery();

        try {
            $port = $this->getQueryPort($server);
            $engine = $this->getEngineType($server->mod);

            $query->Connect($server->ip, $port, $timeout, $engine);

            $players = $query->GetPlayers();

            return is_array($players) ? $players : [];
        } catch (Exception $e) {
            logs()->warning("Failed to get player list for server #{$server->id}: " . $e->getMessage());

            return [];
        } finally {
            $query->Disconnect();
        }
    }

    public function getServerInfo(Server $server, int $timeout = 3): ?array
    {
        $query = new SourceQuery();

        try {
            $port = $this->getQueryPort($server);
            $engine = $this->getEngineType($server->mod);

            $query->Connect($server->ip, $port, $timeout, $engine);

            $info = $query->GetInfo();

            return is_array($info) ? $info : null;
        } catch (Exception $e) {
            return null;
        } finally {
            $query->Disconnect();
        }
    }

    public function testRcon(Server $server, int $timeout = 3): bool
    {
        if (!$this->isAvailable($server)) {
            return false;
        }

        try {
            $this->execute($server, 'status', $timeout);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    private function getEngineType(string $mod): int
    {
        if (in_array($mod, self::GOLDSOURCE_MODS, true)) {
            return SourceQuery::GOLDSOURCE;
        }

        return SourceQuery::SOURCE;
    }

    private function getRconPort(Server $server): int
    {
        $rconPort = $server->getSetting('rcon_port');

        return $rconPort ? (int) $rconPort : $server->port;
    }

    private function getQueryPort(Server $server): int
    {
        $queryPort = $server->getSetting('query_port');

        return $queryPort ? (int) $queryPort : $server->port;
    }
}
