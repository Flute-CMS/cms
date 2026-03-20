<?php

namespace Flute\Core\Template;

use ScssPhp\ScssPhp\OutputStyle;

/**
 * Hybrid SCSS compiler: tries dart-sass binary first, falls back to scssphp.
 *
 * dart-sass is ~50-100x faster than pure-PHP scssphp. The binary is auto-downloaded
 * by the DartSassBinaryManager during composer install or on first use.
 */
class NativeSassCompiler
{
    private const BINARY_DIR = BASE_PATH . 'storage' . DIRECTORY_SEPARATOR . 'bin';

    /** @var string[] Command prefix to invoke dart-sass (e.g. ['dart.exe', 'sass.snapshot'] or ['sass']) */
    private array $binaryCommand = [];

    private bool $nativeAvailable;

    private bool $checkedNative = false;

    /**
     * @var string[] Import paths for SCSS resolution
     */
    private array $importPaths = [];

    private string $outputStyle = OutputStyle::EXPANDED;

    /**
     * Check if the native dart-sass binary is available and executable.
     * Auto-downloads on first check if not found.
     */
    public function isNativeAvailable(): bool
    {
        if ($this->checkedNative) {
            return $this->nativeAvailable;
        }

        $this->checkedNative = true;
        $this->nativeAvailable = false;

        if (!$this->isProcOpenAvailable()) {
            return false;
        }

        $command = $this->findBinaryCommand();

        // Auto-install if not found and never attempted
        if ($command === null && !$this->hasAttemptedInstall()) {
            $this->markInstallAttempted();

            if (self::downloadBinary()) {
                $command = $this->findBinaryCommand();
            }
        }

        if ($command === null) {
            return false;
        }

        $this->binaryCommand = $command;
        $this->nativeAvailable = true;

        return true;
    }

    /**
     * Check if we already tried to install (avoid retrying every request).
     */
    private function hasAttemptedInstall(): bool
    {
        return is_file(self::BINARY_DIR . DIRECTORY_SEPARATOR . '.install-attempted');
    }

    /**
     * Mark that we attempted installation (successful or not).
     */
    private function markInstallAttempted(): void
    {
        $dir = self::BINARY_DIR;
        if (!is_dir($dir)) {
            @mkdir($dir, 0o755, true);
        }

        @file_put_contents($dir . DIRECTORY_SEPARATOR . '.install-attempted', (string) time());
    }

    public function setOutputStyle(string $style): void
    {
        $this->outputStyle = $style;
    }

    public function setImportPaths(array $paths): void
    {
        $this->importPaths = $paths;
    }

    public function addImportPath(string $path): void
    {
        $this->importPaths[] = $path;
    }

    /**
     * Compile SCSS string to CSS.
     * Tries native dart-sass first, falls back to scssphp on failure.
     *
     * @param string $scss The SCSS content to compile
     * @param TemplateScssCompiler $fallback The scssphp compiler for fallback
     *
     * @return string Compiled CSS
     */
    public function compile(string $scss, TemplateScssCompiler $fallback): string
    {
        if ($this->isNativeAvailable()) {
            $result = $this->compileNative($scss);
            if ($result !== null) {
                return $result;
            }

            // Per-file fallback: dart-sass failed on this SCSS (strict mode),
            // but keep native available for other files that may compile fine.
        }

        return $fallback->compileString($scss)->getCss();
    }

    /**
     * Compile SCSS using the native dart-sass binary via proc_open + stdin/stdout.
     *
     * @return string|null CSS output, or null on failure
     */
    private function compileNative(string $scss): ?string
    {
        $args = array_merge($this->binaryCommand, [
            '--stdin',
            '--no-source-map',
            '--charset',
            '--silence-deprecation=import,global-builtin',
        ]);

        // Output style
        if ($this->outputStyle === OutputStyle::COMPRESSED || $this->outputStyle === 'compressed') {
            $args[] = '--style=compressed';
        } else {
            $args[] = '--style=expanded';
        }

        // Import paths
        foreach (array_unique($this->importPaths) as $path) {
            if (is_dir($path)) {
                $args[] = '--load-path=' . str_replace('\\', '/', $path);
            }
        }

        $descriptors = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        $process = @proc_open($args, $descriptors, $pipes);

        if (!is_resource($process)) {
            return null;
        }

        // Write SCSS to stdin
        fwrite($pipes[0], $scss);
        fclose($pipes[0]);

        // Read stdout (compiled CSS)
        $css = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        // Read stderr (errors)
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            if (function_exists('logs')) {
                logs()->error('dart-sass error: ' . trim($stderr));
            }

            return null;
        }

        return $css ?: '';
    }

    /**
     * Find the dart-sass binary and return the command array to invoke it.
     * On Windows, calls dart.exe + sass.snapshot directly to avoid .bat overhead (~500ms).
     *
     * @return string[]|null Command arguments, or null if not found
     */
    private function findBinaryCommand(): ?array
    {
        $dartSassDir = self::BINARY_DIR . DIRECTORY_SEPARATOR . 'dart-sass';

        // 1. Check project storage/bin/dart-sass/ (auto-downloaded)
        if (is_dir($dartSassDir)) {
            $srcDir = $dartSassDir . DIRECTORY_SEPARATOR . 'src';
            $snapshot = $srcDir . DIRECTORY_SEPARATOR . 'sass.snapshot';

            if (PHP_OS_FAMILY === 'Windows') {
                $dart = $srcDir . DIRECTORY_SEPARATOR . 'dart.exe';
                if (is_file($dart) && is_file($snapshot)) {
                    return [$dart, $snapshot];
                }
            } else {
                $dart = $srcDir . DIRECTORY_SEPARATOR . 'dart';
                if (is_file($dart) && is_file($snapshot) && is_executable($dart)) {
                    return [$dart, $snapshot];
                }

                // Fallback to shell script
                $sass = $dartSassDir . DIRECTORY_SEPARATOR . 'sass';
                if (is_file($sass) && is_executable($sass)) {
                    return [$sass];
                }
            }
        }

        // 2. Check if dart-sass is available system-wide
        $which = PHP_OS_FAMILY === 'Windows' ? 'where' : 'which';
        $redirect = PHP_OS_FAMILY === 'Windows' ? '2>NUL' : '2>/dev/null';
        $result = @shell_exec("{$which} sass {$redirect}");
        if ($result !== null) {
            $path = trim(explode("\n", $result)[0]);
            if ($path !== '' && is_file($path)) {
                return [$path];
            }
        }

        return null;
    }

    /**
     * Check if proc_open is available (not disabled by hosting).
     */
    private function isProcOpenAvailable(): bool
    {
        if (!function_exists('proc_open')) {
            return false;
        }

        $disabled = ini_get('disable_functions');
        if ($disabled && stripos($disabled, 'proc_open') !== false) {
            return false;
        }

        return true;
    }

    /**
     * Download the dart-sass binary for the current platform.
     *
     * @return bool True if downloaded successfully
     */
    public static function downloadBinary(?string $version = null): bool
    {
        $version = $version ?? '1.98.0';
        $platform = self::detectPlatform();

        if ($platform === null) {
            return false;
        }

        $url = sprintf(
            'https://github.com/sass/dart-sass/releases/download/%s/dart-sass-%s-%s.%s',
            $version,
            $version,
            $platform,
            PHP_OS_FAMILY === 'Windows' ? 'zip' : 'tar.gz',
        );

        $binDir = self::BINARY_DIR;
        if (!is_dir($binDir)) {
            mkdir($binDir, 0o755, true);
        }

        $tmpFile =
            $binDir . DIRECTORY_SEPARATOR . 'dart-sass-download.' . ( PHP_OS_FAMILY === 'Windows' ? 'zip' : 'tar.gz' );

        // Download
        $context = stream_context_create([
            'http' => [
                'follow_location' => true,
                'timeout' => 60,
                'user_agent' => 'Flute-CMS/2.0',
            ],
            'ssl' => [
                'verify_peer' => true,
            ],
        ]);

        $data = @file_get_contents($url, false, $context);
        if ($data === false) {
            // Try with curl if file_get_contents failed
            if (function_exists('curl_init')) {
                $data = self::downloadWithCurl($url);
            }

            if ($data === false) {
                return false;
            }
        }

        file_put_contents($tmpFile, $data);

        // Extract
        $success = PHP_OS_FAMILY === 'Windows'
            ? self::extractZip($tmpFile, $binDir)
            : self::extractTarGz($tmpFile, $binDir);

        @unlink($tmpFile);

        if (!$success) {
            return false;
        }

        // Archive extracts as dart-sass/ which is exactly where findBinary() looks
        $extractedDir = $binDir . DIRECTORY_SEPARATOR . 'dart-sass';
        $binaryName = PHP_OS_FAMILY === 'Windows' ? 'sass.bat' : 'sass';
        $binaryPath = $extractedDir . DIRECTORY_SEPARATOR . $binaryName;

        if (!is_file($binaryPath)) {
            return false;
        }

        // Ensure binary is executable on Unix
        if (PHP_OS_FAMILY !== 'Windows') {
            @chmod($binaryPath, 0o755);
            // Also make the dart runtime executable
            $dartBinary = $extractedDir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'dart';
            if (is_file($dartBinary)) {
                @chmod($dartBinary, 0o755);
            }
        }

        return true;
    }

    /**
     * Detect the platform string for dart-sass releases.
     */
    private static function detectPlatform(): ?string
    {
        $os = PHP_OS_FAMILY;
        $arch = php_uname('m');

        // Normalize architecture
        $archMap = [
            'x86_64' => 'x64',
            'amd64' => 'x64',
            'aarch64' => 'arm64',
            'arm64' => 'arm64',
            'armv7l' => 'arm',
        ];

        $normalizedArch = $archMap[strtolower($arch)] ?? null;

        if ($normalizedArch === null) {
            return null;
        }

        return match ($os) {
            'Windows' => "windows-{$normalizedArch}",
            'Darwin' => "macos-{$normalizedArch}",
            'Linux' => "linux-{$normalizedArch}",
            default => null,
        };
    }

    private static function downloadWithCurl(string $url): string|false
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_USERAGENT => 'Flute-CMS/2.0',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode >= 200 && $httpCode < 300 ? $data : false;
    }

    private static function extractZip(string $zipFile, string $targetDir): bool
    {
        try {
            app(\Flute\Core\Support\FileUploader::class)->safeExtractZip($zipFile, $targetDir);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private static function extractTarGz(string $tarFile, string $targetDir): bool
    {
        if (class_exists('PharData')) {
            try {
                $phar = new \PharData($tarFile);
                $phar->decompress();

                $tarOnly = preg_replace('/\.gz$/', '', $tarFile);
                $pharTar = new \PharData($tarOnly);
                $pharTar->extractTo($targetDir, null, true);
                @unlink($tarOnly);

                return true;
            } catch (\Throwable $e) {
                // Fall through to shell extraction
            }
        }

        // Try shell-based extraction
        if (function_exists('proc_open')) {
            $cmd = sprintf('tar -xzf %s -C %s 2>/dev/null', escapeshellarg($tarFile), escapeshellarg($targetDir));
            @exec($cmd, $output, $exitCode);

            return $exitCode === 0;
        }

        return false;
    }

    private static function copyDirectory(string $src, string $dst): void
    {
        if (!is_dir($dst)) {
            mkdir($dst, 0o755, true);
        }

        $dir = opendir($src);
        while (( $file = readdir($dir) ) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $srcPath = $src . DIRECTORY_SEPARATOR . $file;
            $dstPath = $dst . DIRECTORY_SEPARATOR . $file;

            if (is_dir($srcPath)) {
                self::copyDirectory($srcPath, $dstPath);
            } else {
                copy($srcPath, $dstPath);
            }
        }

        closedir($dir);
    }

    private static function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                @rmdir($file->getRealPath());
            } else {
                @unlink($file->getRealPath());
            }
        }

        @rmdir($dir);
    }
}
