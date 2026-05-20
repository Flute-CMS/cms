<?php

namespace Flute\Core\Template\Concerns;

use MatthiasMullie\Minify;
use Padaliyajay\PHPAutoprefixer\Autoprefixer;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;

trait AssetCaching
{
    public static function getAssetsCompileTime(): float
    {
        return self::$assetsCompileTime;
    }

    public function clearCache(): void
    {
        $this->assetPathCache = [];
        $this->compilationCache = [];
        $this->fallbackAssetPaths = [];
        $this->cachedThemeFallbackOrder = null;
    }

    public function clearStyleCache(): void
    {
        $cssCachePath = BASE_PATH . '/public/assets/css/cache/*';
        $filesystem = new Filesystem();
        $filesystem->remove(glob($cssCachePath));
        $this->clearCache();
    }

    public function getCacheStats(): array
    {
        return [
            'asset_path_cache_size' => count($this->assetPathCache),
            'compilation_cache_size' => count($this->compilationCache),
            'debug_mode' => $this->debugMode,
            'context' => $this->context,
        ];
    }

    protected function getCacheDir(string $type): string
    {
        return "assets/{$type}/cache/{$this->context}/";
    }

    protected function getStaleCacheDir(string $type): string
    {
        return "assets/{$type}/cache_stale/{$this->context}/";
    }

    protected function saveAsset(string $path, string $content): void
    {
        $content = $this->minifyContent($path, $content);
        $this->ensureDirectoryExists(dirname($path));

        if (file_put_contents($path, $content, LOCK_EX) === false) {
            logs()->error("Failed to write asset to path: {$path}");
        }
    }

    protected function copyAsset(string $sourcePath, string $destinationPath): void
    {
        $this->ensureDirectoryExists(dirname($destinationPath));

        if (!copy($sourcePath, $destinationPath)) {
            logs()->error("Failed to copy asset from {$sourcePath} to {$destinationPath}");
        }
    }

    protected function minifyContent(string $path, string $content): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($extension === 'js' && $this->minifyAssets) {
            $minifier = new Minify\JS();
            $minifier->add($content);

            return $minifier->minify();
        }

        if ($extension === 'css' && $this->minifyAssets) {
            if ($this->autoprefixAssets) {
                if ($this->autoprefixMaxBytes > 0 && strlen($content) > $this->autoprefixMaxBytes) {
                    logs()->warning(sprintf(
                        'Autoprefix skipped: CSS size %d bytes exceeds limit %d',
                        strlen($content),
                        $this->autoprefixMaxBytes,
                    ));
                } else {
                    if (!is_debug()) {
                        $originalContent = $content;
                        $autoprefixTimeout = 10;

                        try {
                            $startTime = microtime(true);
                            $autoprefixer = new Autoprefixer($content);
                            $content = $autoprefixer->compile();
                            $elapsed = microtime(true) - $startTime;

                            if ($elapsed > $autoprefixTimeout) {
                                logs()->warning(sprintf(
                                    'Autoprefixer took %.2fs (threshold: %ds), CSS size: %d bytes',
                                    $elapsed,
                                    $autoprefixTimeout,
                                    strlen($originalContent),
                                ));
                            }
                        } catch (Throwable $e) {
                            logs()->warning('Autoprefixer skipped: ' . $e->getMessage());
                            $content = $originalContent;
                        }
                    }
                }
            }

            if ($content === '') {
                return '';
            }

            $minifier = new Minify\CSS();
            $minifier->add($content);

            return $minifier->minify();
        }

        return $content;
    }

    protected function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0o755, true);
        }
    }

    private function assetCacheIdentity(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        $basePath = rtrim(str_replace('\\', '/', BASE_PATH), '/') . '/';

        if (str_starts_with($normalized, $basePath)) {
            return substr($normalized, strlen($basePath));
        }

        return $normalized;
    }

    private function withFileLock(string $lockFile, callable $callback): void
    {
        $handle = @fopen($lockFile, 'w+');
        if ($handle === false) {
            $callback();

            return;
        }

        if (flock($handle, LOCK_EX | LOCK_NB)) {
            try {
                $callback();
            } finally {
                flock($handle, LOCK_UN);
                fclose($handle);
                @unlink($lockFile);
            }

            return;
        }

        $waited = 0;
        while (!flock($handle, LOCK_SH | LOCK_NB)) {
            if ($waited >= 10) {
                fclose($handle);

                return;
            }
            usleep(100_000);
            $waited += 0.1;
        }
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
