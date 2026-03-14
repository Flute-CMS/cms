<?php

namespace Flute\Core\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class CacheClearCommand extends Command
{
    protected static $defaultName = 'cache:clear';

    protected function configure()
    {
        $this
            ->setName('cache:clear')
            ->setDescription('Clears application cache (with SWR rotation) and asset caches.')
            ->addOption('full', null, InputOption::VALUE_NONE, 'Also clears templates, translations, logs and proxies caches (may be expensive under load).')
            ->setHelp('Clears Flute cache. Use --full to additionally purge templates/translations/logs/proxies caches.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $cacheDir = storage_path('app/cache');
        $cacheStaleDir = storage_path('app/cache_stale');
        $full = (bool) $input->getOption('full');

        $cssCacheDir = public_path('assets/css/cache');
        $cssCacheStaleDir = public_path('assets/css/cache_stale');
        $jsCacheDir = public_path('assets/js/cache');
        $jsCacheStaleDir = public_path('assets/js/cache_stale');

        try {
            $filesystem = new Filesystem();

            \Flute\Core\Cache\SWRQueue::flush();

            if (function_exists('cache_bump_epoch')) {
                cache_bump_epoch();
            }
            if (function_exists('cache_warmup_mark')) {
                cache_warmup_mark();
            }

            // Remove both cache and stale directories entirely.
            // Explicit cache clear should wipe everything — no stale fallback.
            $this->removeAndRecreate($filesystem, $cacheDir);
            $this->removeAndRecreate($filesystem, $cacheStaleDir);
            $this->removeAndRecreate($filesystem, $cssCacheDir);
            $this->removeAndRecreate($filesystem, $cssCacheStaleDir);
            $this->removeAndRecreate($filesystem, $jsCacheDir);
            $this->removeAndRecreate($filesystem, $jsCacheStaleDir);

            // Clear compiled config cache
            $configCompiled = storage_path('app/cache/config_compiled.php');
            if (file_exists($configCompiled)) {
                @unlink($configCompiled);
                if (function_exists('opcache_invalidate')) {
                    @opcache_invalidate($configCompiled, true);
                }
            }

            // Always clear Blade view cache
            $viewsPath = storage_path('app/views/*');
            $viewFiles = glob($viewsPath);
            if ($viewFiles) {
                $filesystem->remove($viewFiles);
            }

            if ($full) {
                foreach ([
                    storage_path('app/proxies/*'),
                    storage_path('app/translations/*'),
                    storage_path('logs/*'),
                ] as $pattern) {
                    $files = glob($pattern);
                    if ($files) {
                        $filesystem->remove($files);
                    }
                }
            }

            $io->success('Flute cache has been deleted successfully.');

            return Command::SUCCESS;
        } catch (IOException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
    }

    protected function fixPermissions(string $path): void
    {
        if (!function_exists('posix_getuid')) {
            return;
        }

        if (posix_getuid() === 0) { // Running as root
            $user = posix_getpwnam('www-data');
            if ($user) {
                @chown($path, $user['uid']);
                @chgrp($path, $user['gid']);
            }
        }
    }

    private function removeAndRecreate(Filesystem $filesystem, string $dir): void
    {
        if (is_dir($dir)) {
            $filesystem->remove($dir);
        }

        @mkdir($dir, 0o775, true);
        $this->fixPermissions($dir);
    }
}
