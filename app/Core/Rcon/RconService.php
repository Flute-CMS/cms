<?php

namespace Flute\Core\Rcon;

use Flute\Core\Database\Entities\Server;
use Flute\Core\Rcon\Drivers\RustRconDriver;
use Flute\Core\Rcon\Drivers\SourceRconDriver;
use RuntimeException;

class RconService
{
    /** @var array<string, RconDriverInterface> */
    private array $drivers = [];

    /** @var array<string, class-string<RconDriverInterface>> */
    private array $driverMap = [];

    /**
     * Mods that use Source RCON (TCP binary protocol).
     * Also used by Minecraft Java Edition.
     */
    private const SOURCE_RCON_MODS = [
        '730',
        '240',
        '10',
        '440',
        '550',
        '4000', // CS2, CSS, CS1.6, TF2, L4D2, GMod
        '221100',
        '107410',
        '346110',
        '251570', // DayZ, Arma3, ARK, 7D2D
        '304930',
        '108600',
        '282440', // Unturned, PZ, QuakeLive
        'all_hl_games_mods',
        'minecraft', // Minecraft uses same TCP RCON protocol
    ];

    /**
     * Mods that use Rust WebSocket RCON.
     */
    private const RUST_RCON_MODS = [
        '252490',
        'rust',
    ];

    /**
     * Execute an RCON command on a game server.
     */
    public function execute(Server $server, string $command, int $timeout = 3): string
    {
        if (empty($server->rcon)) {
            throw new RuntimeException("RCON password is not configured for server #{$server->id}");
        }

        $driver = $this->resolveDriver($server->mod);

        if ($driver === null) {
            throw new RuntimeException("No RCON driver available for mod: {$server->mod}");
        }

        $port = $this->getRconPort($server);

        return $driver->execute($server->ip, $port, $server->rcon, $command, $timeout);
    }

    /**
     * Check if RCON is available for a server.
     */
    public function isAvailable(Server $server): bool
    {
        return !empty($server->rcon) && $this->resolveDriver($server->mod) !== null;
    }

    /**
     * Test RCON connection.
     */
    public function test(Server $server, int $timeout = 3): bool
    {
        if (!$this->isAvailable($server)) {
            return false;
        }

        $driver = $this->resolveDriver($server->mod);

        if ($driver === null) {
            return false;
        }

        $port = $this->getRconPort($server);

        return $driver->test($server->ip, $port, $server->rcon, $timeout);
    }

    /**
     * Register a custom RCON driver for a game mod.
     */
    public function registerDriver(string $mod, string $driverClass): void
    {
        $this->driverMap[$mod] = $driverClass;
        unset($this->drivers[$driverClass]);
    }

    private function resolveDriver(string $mod): ?RconDriverInterface
    {
        // Custom registered drivers take priority
        if (isset($this->driverMap[$mod])) {
            $class = $this->driverMap[$mod];

            if (!isset($this->drivers[$class])) {
                $this->drivers[$class] = new $class();
            }

            return $this->drivers[$class];
        }

        if (in_array($mod, self::SOURCE_RCON_MODS, true)) {
            if (!isset($this->drivers[SourceRconDriver::class])) {
                $this->drivers[SourceRconDriver::class] = new SourceRconDriver();
            }

            return $this->drivers[SourceRconDriver::class];
        }

        if (in_array($mod, self::RUST_RCON_MODS, true)) {
            if (!isset($this->drivers[RustRconDriver::class])) {
                $this->drivers[RustRconDriver::class] = new RustRconDriver();
            }

            return $this->drivers[RustRconDriver::class];
        }

        // Fallback to Source RCON for unknown mods
        if (!isset($this->drivers[SourceRconDriver::class])) {
            $this->drivers[SourceRconDriver::class] = new SourceRconDriver();
        }

        return $this->drivers[SourceRconDriver::class];
    }

    private function getRconPort(Server $server): int
    {
        $settings = method_exists($server, 'getSettings') ? $server->getSettings() : [];
        $rconPort = $settings['rcon_port'] ?? null;

        if ($rconPort) {
            return (int) $rconPort;
        }

        // Rust default RCON port = game port - 2 + 1 = game port + 1... actually it's 28016 by default
        // But we should use the configured port, fallback to game port
        return (int) $server->port;
    }
}
