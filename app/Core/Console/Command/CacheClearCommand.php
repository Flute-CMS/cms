<?php

namespace Flute\Core\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
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
            ->setDescription('Clears the cache in storage/app/cache and deletes styles cache.')
            ->setHelp('This command allows you to clear the all Flute cache.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new SymfonyStyle($input, $output);

        $cachePath = BASE_PATH . '/storage/app/cache/*';
        $viewsPath = BASE_PATH . '/storage/app/views/*';
        $logsPath = BASE_PATH . '/storage/logs/*';
        $proxiesPath = BASE_PATH . '/storage/app/proxies/*';
        $translationsPath = BASE_PATH . '/storage/app/translations/*';
        $cssCachePath = BASE_PATH . '/public/assets/css/cache/*';
        $jsCachePath = BASE_PATH . '/public/assets/js/cache/*';

        try {
            $filesystem = new Filesystem();

            $filesystem->remove(files: glob($cachePath));

            $filesystem->remove(glob($proxiesPath));

            $filesystem->remove(glob($cssCachePath));

            $filesystem->remove(glob($jsCachePath));

            $filesystem->remove(glob($translationsPath));

            $filesystem->remove(glob($viewsPath));

            $filesystem->remove(glob($logsPath));

            $io->success('Flute cache has been deleted successfully.');

            return Command::SUCCESS;
        } catch (IOException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}
