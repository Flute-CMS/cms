<?php

namespace Flute\Admin\Packages\Server\Services;

use Exception;
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
        foreach (glob(path('public/assets/img/ranks/*')) as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $name = basename($dir);
            $previews = $this->getRankPreviews($dir, 5);

            $previewHtml = '<div class="rank-pack-option"><span class="rank-pack-option__name">' . e($name) . '</span>';
            if (!empty($previews)) {
                $previewHtml .= '<span class="rank-pack-option__previews">';
                foreach ($previews as $src) {
                    $previewHtml .= '<img src="' . url($src) . '" alt="" loading="lazy" />';
                }
                $previewHtml .= '</span>';
            }
            $previewHtml .= '</div>';

            $ranks[$name] = [
                'text' => $name,
                'optionHtml' => $previewHtml,
                'itemHtml' => $previewHtml,
            ];
        }

        return $ranks;
    }

    /**
     * Detect the best available format in a rank pack directory.
     * Priority: svg > webp > png > jpg > gif > jpeg
     */
    public function detectBestFormat(string $dir): string
    {
        $priority = ['svg', 'webp', 'png', 'jpg', 'gif', 'jpeg'];

        foreach ($priority as $ext) {
            if (glob($dir . '/*.' . $ext)) {
                return $ext;
            }
        }

        return 'webp';
    }

    /**
     * Get preview image paths for a rank pack directory
     */
    private function getRankPreviews(string $dir, int $max = 5): array
    {
        $previews = [];
        $files = glob($dir . '/*.{webp,png,svg,jpg,gif,jpeg}', GLOB_BRACE);

        if (!$files) {
            return [];
        }

        // Sort numerically by filename
        usort($files, function ($a, $b) {
            return (int) basename($a) - (int) basename($b);
        });

        $count = 0;
        foreach ($files as $file) {
            if ($count >= $max) {
                break;
            }
            $previews[] = 'assets/img/ranks/' . basename($dir) . '/' . basename($file);
            $count++;
        }

        return $previews;
    }

    /**
     * Upload and extract a custom rank pack from ZIP archive
     */
    public function uploadRankPack(\Symfony\Component\HttpFoundation\File\UploadedFile $file): string
    {
        $uploader = app(\Flute\Core\Support\FileUploader::class);

        $zipRelPath = $uploader->uploadZip($file, 20);
        $zipFullPath = path('public/' . $zipRelPath);

        $zip = new \ZipArchive();
        if ($zip->open($zipFullPath) !== true) {
            @unlink($zipFullPath);
            throw new Exception(__('admin-server.ranks_upload.invalid_archive'));
        }

        $imageExtensions = ['webp', 'png', 'svg', 'jpg', 'gif', 'jpeg'];
        $imageFiles = [];
        $packName = null;
        $inSubdir = false;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);

            // Skip directories, macOS resource forks, hidden files
            if (
                str_ends_with($entry, '/')
                || str_starts_with($entry, '__MACOSX')
                || str_starts_with(basename($entry), '.')
            ) {
                continue;
            }

            $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
            if (!in_array($ext, $imageExtensions, true)) {
                continue;
            }

            $parts = explode('/', $entry);
            if (count($parts) === 2) {
                $inSubdir = true;
                $packName ??= $parts[0];
            }

            $imageFiles[] = $entry;
        }

        $zip->close();

        if (empty($imageFiles)) {
            @unlink($zipFullPath);
            throw new Exception(__('admin-server.ranks_upload.no_images'));
        }

        if (!$inSubdir) {
            $packName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $packName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $packName);
        }

        if (empty($packName)) {
            @unlink($zipFullPath);
            throw new Exception(__('admin-server.ranks_upload.no_images'));
        }

        // Extract to temp, then copy only image files
        $tempDir = path('storage/app/temp_ranks_' . bin2hex(random_bytes(8)));
        mkdir($tempDir, 0755, true);
        $uploader->safeExtractZip($zipFullPath, $tempDir);

        $destDir = path('public/assets/img/ranks/' . $packName);
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        foreach ($imageFiles as $entry) {
            $src = $tempDir . '/' . $entry;
            if (is_file($src)) {
                copy($src, $destDir . '/' . basename($entry));
            }
        }

        $this->removeDirectory($tempDir);
        @unlink($zipFullPath);

        return $packName;
    }

    /**
     * Remove directory recursively
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
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
        if (!empty($data['rcon'])) {
            $server->rcon = $data['rcon'];
        } elseif (!$server->rcon) {
            $server->rcon = null;
        }
        $server->display_ip = $data['display_ip'] ?? null;
        $server->enabled = filter_var($data['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
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
            throw new Exception('Подключение не найдено.');
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
