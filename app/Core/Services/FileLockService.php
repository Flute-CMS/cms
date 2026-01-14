<?php

declare(strict_types=1);

namespace Flute\Core\Services;

use RuntimeException;

/**
 * Exception thrown when lock acquisition times out due to contention.
 * This is a separate class to distinguish from directory/file errors.
 */
class LockAcquireTimeoutException extends RuntimeException
{
}

/**
 * Service for handling file locks using flock().
 *
 * IMPORTANT LIMITATIONS:
 * - Works ONLY on LOCAL filesystems (ext4, xfs, NTFS, etc.)
 * - Does NOT work reliably on NFS, SMB, CIFS, or network-mounted volumes
 * - For distributed systems (multiple servers), use Redis/DB locks (e.g., Symfony Lock Component)
 *
 * Lock files are automatically released by the OS when:
 * - The process crashes (segfault, OOM, exception)
 * - The server restarts
 * - The file handle is closed
 *
 * Lock files are NOT deleted after use. This is intentional to avoid race conditions.
 *
 * API DESIGN:
 * - Use withLock() for most cases (automatic, reentrant, exception-safe)
 * - Use acquireLock()/releaseLock() only for advanced cases where you need manual control
 * - DO NOT mix withLock() and acquireLock() on the same file in nested calls
 * - Manual API (acquireLock/releaseLock) is NOT reentrant - use withLock() for nested locking
 *
 * @see https://man7.org/linux/man-pages/man2/flock.2.html
 */
class FileLockService
{
    /**
     * Default lock timeout in seconds.
     */
    public const DEFAULT_TIMEOUT = 15.0;

    /**
     * Cache of locks held by withLock() for reentrancy support.
     * Maps normalized lock file path => ['handle' => resource, 'depth' => int]
     *
     * IMPORTANT: This is SEPARATE from manual acquireLock/releaseLock.
     *
     * @var array<string, array{handle: resource, depth: int}>
     */
    private static array $withLockCache = [];

    /**
     * Reverse map: resource ID => normalized path (for O(1) lookup in releaseLock).
     * Only for manual acquireLock/releaseLock API.
     *
     * @var array<int, string>
     */
    private static array $manualLockResourceMap = [];

    /**
     * Set of paths held by manual API (for O(1) isLocked check).
     *
     * @var array<string, bool>
     */
    private static array $manualLockPathSet = [];

    /**
     * Execute callback with exclusive lock (reentrant-safe).
     *
     * If the same process already holds the lock on this file via withLock(),
     * the lock is reused (no deadlock). This supports nested calls.
     *
     * WARNING: Do not call acquireLock() on the same file inside the callback.
     * The two APIs are intentionally separate to prevent accidental lock release.
     *
     * @param string $lockFile Path to the lock file
     * @param callable $callback Callback to execute while holding the lock
     * @param float $timeoutSeconds Maximum seconds to wait for the lock
     * @throws RuntimeException If directory cannot be created or file cannot be opened
     * @throws LockAcquireTimeoutException If lock cannot be acquired within timeout
     * @return mixed Return value from callback
     */
    public static function withLock(string $lockFile, callable $callback, float $timeoutSeconds = self::DEFAULT_TIMEOUT): mixed
    {
        // Create directory first (needed for normalization)
        $dir = dirname($lockFile);
        if (!is_dir($dir) && !mkdir($dir, 0o755, true) && !is_dir($dir)) {
            throw new RuntimeException("Cannot create lock directory: {$dir}");
        }

        $normalizedPath = self::normalizePathViaDirname($lockFile);

        // Reentrancy check: if we already hold this lock via withLock, just increment depth
        if (isset(self::$withLockCache[$normalizedPath])) {
            self::$withLockCache[$normalizedPath]['depth']++;

            try {
                return $callback();
            } finally {
                self::$withLockCache[$normalizedPath]['depth']--;
                // Don't release - outer withLock will handle it
            }
        }

        // Open file in 'c+' mode (Create, Read/Write, NO truncate)
        $handle = fopen($lockFile, 'c+');
        if (!$handle) {
            throw new RuntimeException("Cannot open lock file: {$lockFile}");
        }

        $locked = false;

        try {
            // Try to acquire lock with timeout (using monotonic clock)
            $startNs = hrtime(true);
            $timeoutNs = (int) ($timeoutSeconds * 1_000_000_000);

            while (true) {
                if (flock($handle, LOCK_EX | LOCK_NB)) {
                    $locked = true;

                    break;
                }

                $elapsedNs = hrtime(true) - $startNs;
                if ($elapsedNs >= $timeoutNs) {
                    break;
                }

                usleep(100_000); // 100ms
            }

            if (!$locked) {
                fclose($handle);

                throw new LockAcquireTimeoutException("Could not acquire lock for {$lockFile} after {$timeoutSeconds} seconds");
            }

            // Write PID for debugging
            ftruncate($handle, 0);
            rewind($handle);
            $written = fwrite($handle, (string) getmypid());
            if ($written !== false) {
                fflush($handle);
            }

            // Register in withLock cache (separate from manual API)
            self::$withLockCache[$normalizedPath] = ['handle' => $handle, 'depth' => 1];

            // Execute the callback
            return $callback();

        } finally {
            // Release only if this is the outermost withLock call
            if (isset(self::$withLockCache[$normalizedPath])) {
                self::$withLockCache[$normalizedPath]['depth']--;
                if (self::$withLockCache[$normalizedPath]['depth'] <= 0) {
                    $h = self::$withLockCache[$normalizedPath]['handle'];
                    unset(self::$withLockCache[$normalizedPath]);
                    if (is_resource($h)) {
                        flock($h, LOCK_UN);
                        fclose($h);
                    }
                }
            } elseif (!$locked && isset($handle) && is_resource($handle)) {
                // Lock failed, close handle
                fclose($handle);
            }
        }
    }

    /**
     * Execute callback with exclusive lock, with fallback on timeout.
     *
     * Fallback is called ONLY when lock acquisition times out (contention).
     * Directory creation and file open errors still throw.
     *
     * @param string $lockFile Path to the lock file
     * @param callable $callback Callback to execute while holding the lock
     * @param callable|null $onLockFailed Callback when lock acquisition times out
     * @param float $timeoutSeconds Maximum seconds to wait for the lock
     * @throws RuntimeException If directory cannot be created or file cannot be opened
     * @return mixed Return value from callback or onLockFailed
     */
    public static function withLockOrFallback(
        string $lockFile,
        callable $callback,
        ?callable $onLockFailed = null,
        float $timeoutSeconds = self::DEFAULT_TIMEOUT
    ): mixed {
        try {
            return self::withLock($lockFile, $callback, $timeoutSeconds);
        } catch (LockAcquireTimeoutException) {
            if ($onLockFailed !== null) {
                return $onLockFailed();
            }

            return null;
        }
        // RuntimeException (directory/file errors) propagates up
    }

    /**
     * Try to acquire an exclusive lock (non-blocking).
     *
     * Returns immediately if lock cannot be acquired.
     *
     * WARNING: This is a MANUAL API, separate from withLock().
     * - Do not mix with withLock() on the same file
     * - This API is NOT reentrant (calling twice on same file = deadlock)
     * - Use withLock() for nested/reentrant scenarios
     *
     * @param string $lockFile Path to the lock file
     * @return resource|false File handle on success, false on failure
     */
    public static function acquireLock(string $lockFile)
    {
        $dir = dirname($lockFile);
        if (!is_dir($dir) && !mkdir($dir, 0o755, true) && !is_dir($dir)) {
            return false;
        }

        $handle = fopen($lockFile, 'c+');
        if ($handle === false) {
            return false;
        }

        if (flock($handle, LOCK_EX | LOCK_NB)) {
            // Write PID for debugging
            ftruncate($handle, 0);
            rewind($handle);
            $written = fwrite($handle, (string) getmypid());
            if ($written !== false) {
                fflush($handle);
            }

            // Track for O(1) lookup
            $normalizedPath = self::normalizePathViaDirname($lockFile);
            $resourceId = get_resource_id($handle);
            self::$manualLockResourceMap[$resourceId] = $normalizedPath;
            self::$manualLockPathSet[$normalizedPath] = true;

            return $handle;
        }

        fclose($handle);

        return false;
    }

    /**
     * Try to acquire lock with wait.
     *
     * WARNING: This is a MANUAL API, separate from withLock().
     * - Do not mix with withLock() on the same file
     * - This API is NOT reentrant (calling twice on same file = deadlock)
     * - Use withLock() for nested/reentrant scenarios
     *
     * @param string $lockFile Path to the lock file
     * @param float $timeoutSeconds Maximum seconds to wait for the lock
     * @return resource|false File handle on success, false on failure
     */
    public static function acquireLockWithWait(string $lockFile, float $timeoutSeconds = self::DEFAULT_TIMEOUT)
    {
        $dir = dirname($lockFile);
        if (!is_dir($dir) && !mkdir($dir, 0o755, true) && !is_dir($dir)) {
            return false;
        }

        $handle = fopen($lockFile, 'c+');
        if ($handle === false) {
            return false;
        }

        $startNs = hrtime(true);
        $timeoutNs = (int) ($timeoutSeconds * 1_000_000_000);

        while (true) {
            if (flock($handle, LOCK_EX | LOCK_NB)) {
                // Write PID for debugging
                ftruncate($handle, 0);
                rewind($handle);
                $written = fwrite($handle, (string) getmypid());
                if ($written !== false) {
                    fflush($handle);
                }

                // Track for O(1) lookup
                $normalizedPath = self::normalizePathViaDirname($lockFile);
                $resourceId = get_resource_id($handle);
                self::$manualLockResourceMap[$resourceId] = $normalizedPath;
                self::$manualLockPathSet[$normalizedPath] = true;

                return $handle;
            }

            $elapsedNs = hrtime(true) - $startNs;
            if ($elapsedNs >= $timeoutNs) {
                break;
            }

            usleep(100_000); // 100ms
        }

        fclose($handle);

        return false;
    }

    /**
     * Release a manually acquired lock.
     *
     * WARNING: This only works with locks from acquireLock()/acquireLockWithWait().
     * Locks from withLock() are released automatically.
     *
     * @param resource $handle File handle from acquireLock
     */
    public static function releaseLock($handle): void
    {
        if (!is_resource($handle)) {
            return;
        }

        // O(1) cleanup of tracking maps
        $resourceId = get_resource_id($handle);
        if (isset(self::$manualLockResourceMap[$resourceId])) {
            $path = self::$manualLockResourceMap[$resourceId];
            unset(self::$manualLockResourceMap[$resourceId]);
            unset(self::$manualLockPathSet[$path]);
        }

        flock($handle, LOCK_UN);
        fclose($handle);
    }

    /**
     * Check if a lock file is currently held by any process.
     *
     * Note: This is a point-in-time check and may not be accurate
     * by the time you act on the result.
     *
     * @param string $lockFile Path to the lock file
     * @return bool True if lock is currently held
     */
    public static function isLocked(string $lockFile): bool
    {
        // Need to create dir first for proper normalization
        $dir = dirname($lockFile);
        if (!is_dir($dir)) {
            // Dir doesn't exist = file doesn't exist = not locked
            return false;
        }

        $normalizedPath = self::normalizePathViaDirname($lockFile);

        // O(1) check if we hold the lock via withLock
        if (isset(self::$withLockCache[$normalizedPath])) {
            return true;
        }

        // O(1) check if we hold it via manual API
        if (isset(self::$manualLockPathSet[$normalizedPath])) {
            return true;
        }

        if (!file_exists($lockFile)) {
            return false;
        }

        $handle = fopen($lockFile, 'c+');
        if ($handle === false) {
            return true; // Assume locked if we can't open
        }

        // Try to acquire a non-blocking lock
        if (flock($handle, LOCK_EX | LOCK_NB)) {
            // We got the lock, so it wasn't locked
            flock($handle, LOCK_UN);
            fclose($handle);

            return false;
        }

        fclose($handle);

        return true;
    }

    /**
     * Check if the current process holds a lock on the given file via withLock().
     *
     * @param string $lockFile Path to the lock file
     * @return bool True if this process holds the lock via withLock
     */
    public static function isHeldByWithLock(string $lockFile): bool
    {
        $dir = dirname($lockFile);
        if (!is_dir($dir)) {
            return false;
        }

        return isset(self::$withLockCache[self::normalizePathViaDirname($lockFile)]);
    }

    /**
     * Get the reentrancy depth for a lock file (withLock API only).
     *
     * @param string $lockFile Path to the lock file
     * @return int Depth (0 = not held, 1+ = held with nesting level)
     */
    public static function getWithLockDepth(string $lockFile): int
    {
        $dir = dirname($lockFile);
        if (!is_dir($dir)) {
            return 0;
        }
        $normalizedPath = self::normalizePathViaDirname($lockFile);

        return self::$withLockCache[$normalizedPath]['depth'] ?? 0;
    }

    /**
     * Normalize path to an absolute path using realpath on the directory.
     *
     * This approach works reliably on both Windows and Unix:
     * - Uses realpath() on the existing directory
     * - Appends the basename
     * - No manual drive letter handling needed
     *
     * IMPORTANT: Directory must exist before calling this method.
     */
    private static function normalizePathViaDirname(string $path): string
    {
        $dir = dirname($path);
        $basename = basename($path);

        // Directory should exist at this point (created by callers)
        $realDir = realpath($dir);
        if ($realDir === false) {
            // Fallback: shouldn't happen if callers create dir first
            $realDir = $dir;
        }

        return rtrim($realDir, '/\\') . DIRECTORY_SEPARATOR . $basename;
    }
}
