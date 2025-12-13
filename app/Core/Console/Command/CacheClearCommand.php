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

            if (function_exists('cache_bump_epoch')) {
                cache_bump_epoch();
            }
            if (function_exists('cache_warmup_mark')) {
                cache_warmup_mark();
            }

            // Rotate cache directory for SWR: keep previous values in cache_stale.
            if (is_dir($cacheStaleDir)) {
                $filesystem->remove($cacheStaleDir);
            }

            if (is_dir($cacheDir)) {
                $filesystem->rename($cacheDir, $cacheStaleDir, true);
            }

            if (!is_dir($cacheDir)) {
                @mkdir($cacheDir, 0o755, true);
            }

            // Rotate assets cache for SWR: TemplateAssets can serve stale while recompiling.
            if (is_dir($cssCacheStaleDir)) {
                $filesystem->remove($cssCacheStaleDir);
            }
            if (is_dir($cssCacheDir)) {
                $filesystem->rename($cssCacheDir, $cssCacheStaleDir, true);
            }
            if (!is_dir($cssCacheDir)) {
                @mkdir($cssCacheDir, 0o755, true);
            }

            if (is_dir($jsCacheStaleDir)) {
                $filesystem->remove($jsCacheStaleDir);
            }
            if (is_dir($jsCacheDir)) {
                $filesystem->rename($jsCacheDir, $jsCacheStaleDir, true);
            }
            if (!is_dir($jsCacheDir)) {
                @mkdir($jsCacheDir, 0o755, true);
            }

            if ($full) {
                $proxiesPath = storage_path('app/proxies/*');
                $translationsPath = storage_path('app/translations/*');
                $viewsPath = storage_path('app/views/*');
                $logsPath = storage_path('logs/*');

                $filesystem->remove(glob($proxiesPath));
                $filesystem->remove(glob($translationsPath));
                $filesystem->remove(glob($viewsPath));
                $filesystem->remove(glob($logsPath));
            }

            // Avoid blocking cache clear; schema refresh is handled lazily on demand.

            $io->success('Flute cache has been deleted successfully.');

            return Command::SUCCESS;
        } catch (IOException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}
