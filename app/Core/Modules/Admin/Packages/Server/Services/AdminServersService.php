<?php

namespace Flute\Admin\Packages\Server\Services;

use Flute\Admin\Packages\Server\Contracts\ModDriverInterface;
use Flute\Admin\Packages\Server\Factories\ModDriverFactory;
use Flute\Core\Database\Entities\DatabaseConnection;
use Flute\Core\Database\Entities\Server;

class AdminServersService
{
    /**
     * @var ModDriverFactory
     */
    protected $driverFactory;

    /**
     * Constructor.
     */
    public function __construct(ModDriverFactory $driverFactory)
    {
        $this->driverFactory = $driverFactory;
    }

    /**
     * Get game name by mod identifier
     */
    public function getGameName(string $mod): string
    {
        return $this->getListGames()[$mod] ?? $mod;
    }

    public function getListRanks(): array
    {
        $ranks = [];
        foreach (glob(path('public/assets/img/ranks/*')) as $file) {
            if (is_dir($file)) {
                $ranks[basename($file)] = basename($file);
            }
        }

        return $ranks;
    }
    /**
     * Get game name by mod identifier
     */
    public function getListGames(): array
    {
        return [
            '730' => 'CS 2 / CS:GO',
            '240' => 'CS:S',
            '10' => 'Counter-Strike 1.6',
            '440' => 'Team Fortress 2',
            '550' => 'Left 4 Dead 2',
            '1002' => 'Rag Doll Kung Fu',
            '2400' => 'The Ship',
            '4000' => 'Garry\'s Mod',
            '17710' => 'Nuclear Dawn',
            '70000' => 'Dino D-Day',
            '107410' => 'Arma 3',
            '115300' => 'Call of Duty: Modern Warfare 3',
            '162107' => 'DeadPoly',
            '211820' => 'Starbound',
            '244850' => 'Space Engineers',
            '304930' => 'Unturned',
            '251570' => '7 Days to Die',
            '252490' => 'Rust',
            '282440' => 'Quake Live',
            '346110' => 'ARK: Survival Evolved',
            'minecraft' => 'Minecraft',
            '108600' => 'Project: Zomboid',
            'gta5' => 'GTA 5',
            'samp' => 'SAMP',
            'all_hl_games_mods' => 'HL1 / HL2 Game',
        ];
    }

    /**
     * Save server (create or update)
     */
    public function saveServer(?Server $server, array $data): Server
    {
        if (!$server) {
            $server = new Server();
        }

        $server->name = $data['name'];
        $server->ip = $data['ip'];
        $server->port = (int) $data['port'];
        $server->mod = $data['mod'];
        $server->rcon = $data['rcon'] ?? null;
        $server->display_ip = $data['display_ip'] ?? null;
        $server->enabled = $data['enabled'] === 'true';
        $server->ranks = $data['ranks'] ?? 'default';
        $server->ranks_format = $data['ranks_format'] ?? 'webp';
        $server->ranks_premier = filter_var($data['ranks_premier'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $settings = [];
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'settings__')) {
                $settingKey = substr($key, 10);
                $settings[$settingKey] = $value;
            }
        }

        if (!empty($settings)) {
            $server->setSettings($settings);
        }

        $server->save();

        return $server;
    }

    /**
     * Delete database connection
     */
    public function deleteDbConnection(int $connectionId): void
    {
        $connection = rep(DatabaseConnection::class)->findByPK($connectionId);

        if (!$connection) {
            throw new \Exception('Подключение не найдено.');
        }

        $connection->delete();
    }

    /**
     * Get all registered mod drivers.
     */
    public function getDrivers(): array
    {
        return $this->driverFactory->getDrivers();
    }

    /**
     * Make a mod driver instance.
     */
    public function makeDriver(string $key): ModDriverInterface
    {
        return $this->driverFactory->make($key);
    }

    /**
     * Check if a mod driver exists.
     */
    public function hasDriver(string $key): bool
    {
        return $this->driverFactory->hasDriver($key);
    }
}
