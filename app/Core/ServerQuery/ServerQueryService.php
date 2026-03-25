<?php

namespace Flute\Core\ServerQuery;

use Flute\Core\Database\Entities\Server;
use Flute\Core\ServerQuery\Drivers\FiveMQueryDriver;
use Flute\Core\ServerQuery\Drivers\MinecraftBedrockQueryDriver;
use Flute\Core\ServerQuery\Drivers\MinecraftJavaQueryDriver;
use Flute\Core\ServerQuery\Drivers\SampQueryDriver;
use Flute\Core\ServerQuery\Drivers\ValveQueryDriver;

class ServerQueryService
{
    /** @var array<string, QueryDriverInterface> */
    private array $drivers = [];

    /** @var array<string, class-string<QueryDriverInterface>> */
    private array $driverMap = [];

    private const DEFAULT_TIMEOUT = 3;

    /**
     * Maps game mod identifiers to query driver class names.
     *
     * @var array<string, class-string<QueryDriverInterface>>
     */
    private const MOD_DRIVER_MAP = [
        // Source engine games → Valve A2S
        '730' => ValveQueryDriver::class, // CS2 / CS:GO
        '240' => ValveQueryDriver::class, // CSS
        '10' => ValveQueryDriver::class, // CS 1.6
        '440' => ValveQueryDriver::class, // TF2
        '550' => ValveQueryDriver::class, // L4D2
        '4000' => ValveQueryDriver::class, // Garry's Mod
        '252490' => ValveQueryDriver::class, // Rust
        '221100' => ValveQueryDriver::class, // DayZ
        '107410' => ValveQueryDriver::class, // Arma 3
        '346110' => ValveQueryDriver::class, // ARK: Survival Evolved
        '251570' => ValveQueryDriver::class, // 7 Days to Die
        '304930' => ValveQueryDriver::class, // Unturned
        '108600' => ValveQueryDriver::class, // Project Zomboid
        '282440' => ValveQueryDriver::class, // Quake Live
        '1002' => ValveQueryDriver::class, // Rag Doll Kung Fu
        '2400' => ValveQueryDriver::class, // The Ship
        '17710' => ValveQueryDriver::class, // Nuclear Dawn
        '70000' => ValveQueryDriver::class, // Dino D-Day
        '115300' => ValveQueryDriver::class, // Call of Duty: Modern Warfare 3
        '162107' => ValveQueryDriver::class, // DeadPoly
        '211820' => ValveQueryDriver::class, // Starbound
        '244850' => ValveQueryDriver::class, // Space Engineers
        'rust' => ValveQueryDriver::class,
        'all_hl_games_mods' => ValveQueryDriver::class,

        // Minecraft Java
        'minecraft' => MinecraftJavaQueryDriver::class,

        // Minecraft Bedrock
        'minecraft_bedrock' => MinecraftBedrockQueryDriver::class,

        // SA-MP / open.mp
        'samp' => SampQueryDriver::class,

        // FiveM / RedM (cfx.re)
        'gta5' => FiveMQueryDriver::class,
        'fivem' => FiveMQueryDriver::class,
        'redm' => FiveMQueryDriver::class,
    ];

    /**
     * Query a game server using the appropriate protocol driver.
     */
    public function query(Server $server, ?int $timeout = null): QueryResult
    {
        $settings = method_exists($server, 'getSettings') ? $server->getSettings() : [];
        $timeout ??= self::DEFAULT_TIMEOUT;

        $driver = $this->resolveDriver($server->mod);

        if ($driver === null) {
            logs()->warning("No query driver for game mod: {$server->mod}");

            return new QueryResult();
        }

        return $driver->query($server->ip, (int) $server->port, $timeout, $settings);
    }

    /**
     * Query a server by raw parameters (for testing connections without a Server entity).
     */
    public function queryRaw(string $ip, int $port, string $mod, int $timeout = 3, array $settings = []): QueryResult
    {
        $driver = $this->resolveDriver($mod);

        if ($driver === null) {
            return new QueryResult();
        }

        return $driver->query($ip, $port, $timeout, $settings);
    }

    /**
     * Query multiple servers in parallel, grouping by driver type.
     * Valve servers use non-blocking scatter-gather; others fall back to sequential.
     *
     * @param Server[] $servers
     * @return array<int, QueryResult> keyed by server ID
     */
    public function queryBatch(array $servers, ?int $timeout = null): array
    {
        $timeout ??= self::DEFAULT_TIMEOUT;
        $results = [];

        // Group by driver class
        $groups = [];

        foreach ($servers as $server) {
            $driverClass =
                $this->driverMap[$server->mod] ?? self::MOD_DRIVER_MAP[$server->mod] ?? ValveQueryDriver::class;
            $groups[$driverClass][] = $server;
        }

        // Valve servers: batch query
        if (isset($groups[ValveQueryDriver::class])) {
            $driver = $this->resolveDriver('730'); // ValveQueryDriver
            $batchInput = [];

            foreach ($groups[ValveQueryDriver::class] as $server) {
                $settings = method_exists($server, 'getSettings') ? $server->getSettings() : [];
                $batchInput[$server->id] = [
                    'ip' => $server->ip,
                    'port' => (int) $server->port,
                    'settings' => $settings,
                ];
            }

            $batchResults = $driver->queryBatch($batchInput, $timeout);

            foreach ($batchResults as $id => $result) {
                $results[$id] = $result;
            }

            unset($groups[ValveQueryDriver::class]);
        }

        // Other drivers: sequential (HTTP/TCP, typically fast)
        foreach ($groups as $driverClass => $groupServers) {
            foreach ($groupServers as $server) {
                $settings = method_exists($server, 'getSettings') ? $server->getSettings() : [];
                $driver = $this->resolveDriver($server->mod);

                if ($driver) {
                    $results[$server->id] = $driver->query($server->ip, (int) $server->port, $timeout, $settings);
                } else {
                    $results[$server->id] = new QueryResult();
                }
            }
        }

        return $results;
    }

    /**
     * Register a custom driver for a game mod identifier.
     * Allows modules to extend the service with new game support.
     */
    public function registerDriver(string $mod, string $driverClass): void
    {
        $this->driverMap[$mod] = $driverClass;

        // Clear cached instance if exists
        unset($this->drivers[$driverClass]);
    }

    /**
     * Check if a driver is available for the given game mod.
     */
    public function hasDriver(string $mod): bool
    {
        return isset($this->driverMap[$mod]) || isset(self::MOD_DRIVER_MAP[$mod]);
    }

    /**
     * Get all supported game mod identifiers.
     *
     * @return string[]
     */
    public function getSupportedMods(): array
    {
        return array_unique(array_merge(array_keys(self::MOD_DRIVER_MAP), array_keys($this->driverMap)));
    }

    private function resolveDriver(string $mod): ?QueryDriverInterface
    {
        // Custom registered drivers take priority
        $driverClass = $this->driverMap[$mod] ?? self::MOD_DRIVER_MAP[$mod] ?? null;

        if ($driverClass === null) {
            // Fallback: try Valve A2S for unknown mods (most game servers use it)
            $driverClass = ValveQueryDriver::class;
        }

        if (!isset($this->drivers[$driverClass])) {
            $this->drivers[$driverClass] = new $driverClass();
        }

        return $this->drivers[$driverClass];
    }
}
