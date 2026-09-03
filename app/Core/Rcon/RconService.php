<?php

namespace Flute\Core\Rcon;

use Flute\Core\Database\Entities\Server;
use Flute\Core\Rcon\Drivers\GoldSrcRconDriver;
use Flute\Core\Rcon\Drivers\RustRconDriver;
use Flute\Core\Rcon\Drivers\SourceRconDriver;
use RuntimeException;

class RconService
{
    /**
     * Circuit-breaker thresholds. After {@see CB_FAILURES_OPEN} consecutive failures
     * the server is treated as unreachable for {@see CB_OPEN_TTL} seconds — calls
     * to execute()/test() throw immediately, isAvailable() returns false. Prevents
     * dead servers from blocking FPM workers on TCP timeouts every request.
     */
    private const CB_FAILURES_OPEN = 3;

    private const CB_OPEN_TTL = 300;

    /** @var array<string, RconDriverInterface> */
    private array $drivers = [];

    /** @var array<string, class-string<RconDriverInterface>> */
    private array $driverMap = [];

    /** @var array<string, array{fails:int,opened_at:int}> */
    private static array $breaker = [];

    /**
     * Mods that use Source RCON (TCP binary protocol).
     * Also used by Minecraft Java Edition.
     */
    /**
     * Mods that use GoldSrc RCON (UDP protocol).
     */
    private const GOLDSRC_RCON_MODS = [
        '10', // Counter-Strike 1.6
        'all_hl_games_mods',
    ];

    private const SOURCE_RCON_MODS = [
        '730',
        '240',
        '440',
        '550',
        '4000', // CSS, TF2, L4D2, GMod
        '221100',
        '107410',
        '346110',
        '251570', // DayZ, Arma3, ARK, 7D2D
        '304930',
        '108600',
        '282440', // Unturned, PZ, QuakeLive
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
        $key = $this->breakerKey($server, $port);

        if ($this->isBreakerOpen($key)) {
            throw new RuntimeException("RCON circuit open for {$server->ip}:{$port} (server marked unreachable)");
        }

        try {
            $result = $driver->execute($server->ip, $port, $server->rcon, $command, $timeout);
            $this->recordSuccess($key);

            return $result;
        } catch (RconAuthException $e) {
            // Сервер ответил, просто пароль неверный — не повод объявлять его недоступным.
            throw $e;
        } catch (\Throwable $e) {
            $this->recordFailure($key);

            throw $e;
        }
    }

    /**
     * Check if RCON is available for a server.
     */
    public function isAvailable(Server $server): bool
    {
        if (empty($server->rcon) || $this->resolveDriver($server->mod) === null) {
            return false;
        }

        return !$this->isBreakerOpen($this->breakerKey($server, $this->getRconPort($server)));
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
        $key = $this->breakerKey($server, $port);

        // Проверку запускает админ вручную, и провал чаще означает опечатку в пароле,
        // а не мёртвый сервер — поэтому неудача брейкер не открывает.
        $ok = $driver->test($server->ip, $port, $server->rcon, $timeout);

        if ($ok) {
            $this->recordSuccess($key);
        }

        return $ok;
    }

    /**
     * Manually reset the circuit breaker for a server (e.g. after admin
     * fixes the server config and wants to retry immediately).
     */
    public function resetBreaker(Server $server): void
    {
        unset(self::$breaker[$this->breakerKey($server, $this->getRconPort($server))]);
    }

    private function breakerKey(Server $server, int $port): string
    {
        return $server->ip . ':' . $port;
    }

    private function isBreakerOpen(string $key): bool
    {
        $this->loadBreakerFromApcu($key);
        $state = self::$breaker[$key] ?? null;
        if ($state === null) {
            return false;
        }
        if ($state['fails'] < self::CB_FAILURES_OPEN) {
            return false;
        }
        if (( time() - $state['opened_at'] ) >= self::CB_OPEN_TTL) {
            // Half-open: allow one probe through.
            self::$breaker[$key]['fails'] = self::CB_FAILURES_OPEN - 1;

            return false;
        }

        return true;
    }

    private function recordFailure(string $key): void
    {
        $this->loadBreakerFromApcu($key);
        $state = self::$breaker[$key] ?? ['fails' => 0, 'opened_at' => 0];
        $state['fails']++;
        if ($state['fails'] >= self::CB_FAILURES_OPEN) {
            $state['opened_at'] = time();
        }
        self::$breaker[$key] = $state;
        if (function_exists('apcu_store')) {
            @apcu_store($this->apcuKey($key), $state, self::CB_OPEN_TTL);
        }
    }

    private function recordSuccess(string $key): void
    {
        unset(self::$breaker[$key]);
        if (function_exists('apcu_delete')) {
            @apcu_delete($this->apcuKey($key));
        }
    }

    private function apcuKey(string $key): string
    {
        return 'flute.rcon.cb.' . $key;
    }

    private function loadBreakerFromApcu(string $key): void
    {
        if (isset(self::$breaker[$key]) || !function_exists('apcu_fetch')) {
            return;
        }
        $cached = @apcu_fetch($this->apcuKey($key), $ok);
        if ($ok && is_array($cached) && isset($cached['fails'], $cached['opened_at'])) {
            self::$breaker[$key] = $cached;
        }
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

        if (in_array($mod, self::GOLDSRC_RCON_MODS, true)) {
            if (!isset($this->drivers[GoldSrcRconDriver::class])) {
                $this->drivers[GoldSrcRconDriver::class] = new GoldSrcRconDriver();
            }

            return $this->drivers[GoldSrcRconDriver::class];
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
