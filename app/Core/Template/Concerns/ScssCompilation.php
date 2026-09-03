<?php

namespace Flute\Core\Template\Concerns;

use Flute\Core\Cache\SWRQueue;
use ScssPhp\ScssPhp\Exception\CompilerException;
use ScssPhp\ScssPhp\Exception\SassException;

trait ScssCompilation
{
    private function processScssAsset(string $expression, string $scssPath): string
    {
        if (!file_exists($scssPath)) {
            $pathParts = explode('/', $expression);
            if (count($pathParts) >= 4 && $pathParts[0] === 'Themes') {
                $relativePath = implode('/', array_slice($pathParts, 4));
                $fallbackPath = $this->findAssetWithFallback($relativePath, 'sass');
                if ($fallbackPath) {
                    $scssPath = $fallbackPath;
                } else {
                    return '';
                }
            } else {
                return '';
            }
        }

        $additionalFiles = $this->additionalScssFiles[$this->context] ?? [];
        $sortedScssFiles = $additionalFiles;
        $sortedScssFiles = array_map(fn($path): string => $this->assetCacheIdentity($path), $sortedScssFiles);
        sort($sortedScssFiles);
        $partials = array_map(fn($path): string => $this->assetCacheIdentity($path), $this->additionalPartials);
        $cacheKey = sha1(
            'scss-v4|'
            . $this->assetCacheIdentity($scssPath)
            . implode(',', $sortedScssFiles)
            . implode(',', $partials)
            . $this->context,
        );

        $cssCacheDir = $this->getCacheDir('css');
        $cssPath = $cssCacheDir . "{$cacheKey}.css";
        $cssFullPath = BASE_PATH . 'public/' . $cssPath;

        $cssStaleCacheDir = $this->getStaleCacheDir('css');
        $cssStalePath = $cssStaleCacheDir . "{$cacheKey}.css";
        $cssStaleFullPath = BASE_PATH . 'public/' . $cssStalePath;

        $this->ensureDirectoryExists(dirname($cssFullPath));

        $cssMtime = file_exists($cssFullPath) ? filemtime($cssFullPath) : 0;
        $cssStaleMtime = file_exists($cssStaleFullPath) ? filemtime($cssStaleFullPath) : 0;
        $manifestFullPath = $this->getScssManifestFile($cacheKey);
        $sourceRoots = $this->getScssSourceRoots($scssPath, $additionalFiles);
        $sourceFiles = $this->readScssManifest($manifestFullPath, $sourceRoots);

        if ($sourceFiles === null) {
            $sourceFiles = $this->resolveScssSourceFiles($scssPath, $additionalFiles);
            $this->writeScssManifest($manifestFullPath, $sourceRoots, $sourceFiles);
        }

        $latestSourceMtime = $this->getFilesMaxMtime($sourceFiles);
        $needsRecompile = $cssMtime === 0 || $latestSourceMtime > $cssMtime;

        if ($needsRecompile) {
            $lockFile = $cssFullPath . '.lock';

            if ($this->debugMode || $cssMtime === 0 && $cssStaleMtime === 0) {
                $this->withFileLock($lockFile, function () use (
                    $scssPath,
                    $additionalFiles,
                    $cssFullPath,
                    $manifestFullPath,
                    $sourceRoots,
                ): void {
                    $this->compileScssToCacheFile($scssPath, $cssFullPath);
                    $this->refreshScssManifest($manifestFullPath, $sourceRoots, $scssPath, $additionalFiles);
                });

                if (!file_exists($cssFullPath)) {
                    $this->compileScssToCacheFile($scssPath, $cssFullPath);
                    $this->refreshScssManifest($manifestFullPath, $sourceRoots, $scssPath, $additionalFiles);
                }

                if ($this->debugMode) {
                    $cssMtime = file_exists($cssFullPath) ? filemtime($cssFullPath) : 0;
                    $needsRecompile = false;
                }
            } else {
                SWRQueue::queue('assets.scss.' . $cacheKey, function () use (
                    $lockFile,
                    $scssPath,
                    $additionalFiles,
                    $cssFullPath,
                    $latestSourceMtime,
                    $manifestFullPath,
                    $sourceRoots,
                ): void {
                    $this->withFileLock($lockFile, function () use (
                        $scssPath,
                        $additionalFiles,
                        $cssFullPath,
                        $latestSourceMtime,
                        $manifestFullPath,
                        $sourceRoots,
                    ): void {
                        $cssMtime = file_exists($cssFullPath) ? filemtime($cssFullPath) : 0;
                        if ($cssMtime !== 0 && $latestSourceMtime <= $cssMtime) {
                            return;
                        }

                        $this->compileScssToCacheFile($scssPath, $cssFullPath);
                        $this->refreshScssManifest($manifestFullPath, $sourceRoots, $scssPath, $additionalFiles);
                    });
                });
            }
        }

        if (!$needsRecompile && isset($this->compilationCache[$cacheKey])) {
            return $this->compilationCache[$cacheKey];
        }

        $servedPath = $cssPath;
        $servedVersion = $cssMtime;

        if ($cssMtime === 0 && $cssStaleMtime > 0) {
            $servedPath = $cssStalePath;
            $servedVersion = $cssStaleMtime;
        }

        if ($servedVersion === 0) {
            $this->compileScssToCacheFile($scssPath, $cssFullPath);
            $this->refreshScssManifest($manifestFullPath, $sourceRoots, $scssPath, $additionalFiles);
            $cssMtime = file_exists($cssFullPath) ? filemtime($cssFullPath) : 0;
            $servedPath = $cssPath;
            $servedVersion = $cssMtime ?: time();
        }

        $url = url($servedPath) . "?v={$servedVersion}";

        $result = "<link rel=\"preload\" href=\"{$url}\" as=\"style\">" . "\n<link href=\"{$url}\" rel=\"stylesheet\">";

        if (!$needsRecompile) {
            $this->compilationCache[$cacheKey] = $result;
        }

        return $result;
    }

    private function compileScssToCacheFile(string $scssPath, string $cssFullPath): void
    {
        $importPaths = [dirname($scssPath)];
        foreach ($this->additionalPartials as $partial) {
            $partialPath = path($partial);
            if (file_exists($partialPath)) {
                $importPaths[] = dirname($partialPath);
            }
        }
        foreach ($this->additionalScssFiles[$this->context] ?? [] as $additionalFile) {
            if (file_exists($additionalFile)) {
                $importPaths[] = dirname($additionalFile);
            }
        }
        $importPaths[] = rtrim(str_replace('\\', '/', BASE_PATH . 'app'), '/');

        $baseImportPaths = $this->scssCompiler->getBaseImportPaths();
        $this->scssCompiler->setImportPaths($baseImportPaths);
        $importPaths = array_map(static fn($p) => str_replace('\\', '/', $p), $importPaths);
        foreach (array_unique($importPaths) as $importPath) {
            if (is_dir($importPath)) {
                $this->scssCompiler->addImportPath($importPath);
            }
        }

        $partialsContent = $this->loadSharedPartials();

        $masterScss = $partialsContent . "\n";
        $masterScss .= '@import "' . str_replace('\\', '/', $scssPath) . '";' . "\n";

        foreach ($this->additionalScssFiles[$this->context] ?? [] as $additionalFile) {
            if (file_exists($additionalFile)) {
                $masterScss .= '@import "' . str_replace('\\', '/', $additionalFile) . '";' . "\n";
            }
        }

        $css = $this->compileScss($masterScss);

        if ($css !== '') {
            $this->saveAsset($cssFullPath, $css);
        }
    }

    private function getScssManifestFile(string $cacheKey): string
    {
        return storage_path("app/cache/scss-manifest/{$cacheKey}.php");
    }

    private function getScssSourceRoots(string $scssPath, array $additionalFiles): array
    {
        $roots = [$this->assetCacheIdentity($scssPath)];

        foreach ($this->additionalPartials as $partial) {
            $partialPath = path($partial);
            if (file_exists($partialPath)) {
                $roots[] = $this->assetCacheIdentity($partialPath);
            }
        }

        foreach ($additionalFiles as $additionalFile) {
            if (file_exists($additionalFile)) {
                $roots[] = $this->assetCacheIdentity($additionalFile);
            }
        }

        $roots = array_values(array_unique($roots));
        sort($roots);

        return $roots;
    }

    private function readScssManifest(string $manifestPath, array $sourceRoots): ?array
    {
        if (!is_file($manifestPath)) {
            return null;
        }

        $manifest = @include $manifestPath;
        if (!is_array($manifest)) {
            return null;
        }

        $roots = $manifest['roots'] ?? null;
        $files = $manifest['files'] ?? null;
        if ($roots !== $sourceRoots || !is_array($files)) {
            return null;
        }

        return array_values(array_filter($files, static fn($file) => is_string($file) && $file !== ''));
    }

    private function writeScssManifest(string $manifestPath, array $sourceRoots, array $sourceFiles): void
    {
        $this->ensureDirectoryExists(dirname($manifestPath));

        @file_put_contents(
            $manifestPath,
            '<?php return '
            . var_export([
                'roots' => $sourceRoots,
                'files' => array_values(array_unique($sourceFiles)),
            ], true)
            . ';',
            LOCK_EX,
        );
    }

    private function resolveScssSourceFiles(string $scssPath, array $additionalFiles): array
    {
        $visited = [];
        $files = $this->collectScssDependencies($scssPath, $visited);

        foreach ($this->additionalPartials as $partial) {
            $partialPath = path($partial);
            if (file_exists($partialPath)) {
                $files = array_merge($files, $this->collectScssDependencies($partialPath, $visited));
            }
        }

        foreach ($additionalFiles as $additionalFile) {
            if (file_exists($additionalFile)) {
                $files = array_merge($files, $this->collectScssDependencies($additionalFile, $visited));
            }
        }

        return array_values(array_unique(array_filter($files, static fn($file) => is_string($file) && $file !== '')));
    }

    private function refreshScssManifest(
        string $manifestPath,
        array $sourceRoots,
        string $scssPath,
        array $additionalFiles,
    ): void {
        $this->writeScssManifest(
            $manifestPath,
            $sourceRoots,
            $this->resolveScssSourceFiles($scssPath, $additionalFiles),
        );
    }

    private function getFilesMaxMtime(array $files): int
    {
        $maxMtime = 0;

        foreach ($files as $file) {
            if (!is_string($file)) {
                continue;
            }

            $mtime = @filemtime($file) ?: 0;
            if ($mtime > $maxMtime) {
                $maxMtime = $mtime;
            }
        }

        return $maxMtime;
    }

    private function collectScssDependencies(string $scssPath, array &$visited): array
    {
        $real = realpath($scssPath) ?: $scssPath;
        $real = str_replace('\\', '/', $real);

        if (isset($visited[$real])) {
            return [];
        }

        $visited[$real] = true;
        $dependencies = [$real];

        $content = @file_get_contents($real);
        if ($content === false) {
            return $dependencies;
        }

        if (preg_match_all('/@(import|use|forward)\s+([^;]+);/i', $content, $matches)) {
            foreach ($matches[2] as $rawImports) {
                foreach (explode(',', $rawImports) as $importExpr) {
                    $importExpr = str_replace('\\', '/', trim($importExpr));
                    $importExpr = trim($importExpr, '\'"');

                    if ($importExpr === '') {
                        continue;
                    }

                    if (
                        str_starts_with($importExpr, 'http')
                        || str_contains($importExpr, 'url(')
                        || str_ends_with($importExpr, '.css')
                    ) {
                        continue;
                    }

                    $candidates = $this->resolveScssImportCandidates($importExpr, dirname($real));

                    $found = false;
                    foreach ($candidates as $candidate) {
                        if (is_file($candidate)) {
                            $dependencies = array_merge($dependencies, $this->collectScssDependencies(
                                $candidate,
                                $visited,
                            ));
                            $found = true;

                            break;
                        }
                    }

                    if (!$found && is_development()) {
                        logs('templates')->warning(
                            "SCSS import '{$importExpr}' not found. Tried: " . json_encode($candidates),
                        );
                    }
                }
            }
        }

        return $dependencies;
    }

    private function resolveScssImportCandidates(string $importExpr, string $baseDir): array
    {
        $clean = str_replace(['"', '\''], '', $importExpr);
        $clean = str_replace('\\', '/', $clean);

        $dirs = [
            rtrim(str_replace('\\', '/', $baseDir), '/'),
            rtrim(str_replace('\\', '/', BASE_PATH . 'app'), '/'),
        ];

        $candidates = [];

        foreach ($dirs as $dir) {
            $path = $dir . '/' . $clean;

            foreach ([$path, "{$path}.scss", "{$path}.sass"] as $candidate) {
                $candidates[] = str_replace('\\', '/', $candidate);
            }

            $parts = explode('/', $clean);
            $file = array_pop($parts);
            $prefix = implode('/', $parts);
            $partialBase = $prefix === '' ? "{$dir}/_{$file}" : "{$dir}/{$prefix}/_{$file}";

            foreach ([$partialBase, "{$partialBase}.scss", "{$partialBase}.sass"] as $candidate) {
                $candidates[] = str_replace('\\', '/', $candidate);
            }
        }

        return array_values(array_unique($candidates));
    }

    private function compileScss(string $scssContent): string
    {
        $start = microtime(true);

        try {
            $css = $this->scssCompiler->compileString($scssContent)->getCss();

            self::$assetsCompileTime += microtime(true) - $start;

            return $css;
        } catch (SassException $e) {
            $message = sprintf('SCSS compilation error: %s', $e);

            if ($this->debugMode) {
                throw new CompilerException($message, 0, null);
            }
            logs()->error($message);
        }

        return '';
    }

    private function loadSharedPartials(): string
    {
        $partialsContent = '';

        foreach ($this->additionalPartials as $partialPath) {
            $partialPath = path($partialPath);

            if (file_exists($partialPath)) {
                $content = file_get_contents($partialPath);
                if ($content !== false) {
                    $partialsContent .= $content . "\n";
                } else {
                    logs()->warning("Unable to read SCSS partial: {$partialPath}");
                }
            } else {
                logs()->warning("SCSS partial not found: {$partialPath}");
            }
        }

        return $partialsContent;
    }
}
