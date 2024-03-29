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
            ->setDescription('Clears the cache in storage/app/cache and deletes styles cache.')
            ->setHelp('This command allows you to clear the all Flute cache.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $cachePath = BASE_PATH . '/storage/app/cache/*';
        $viewsPath = BASE_PATH . '/storage/app/views/*';
        $proxiesPath = BASE_PATH . '/storage/app/proxies/*';
        $translationsPath = BASE_PATH . '/storage/app/translations/*';
        $cssCachePath = BASE_PATH . '/public/assets/css/cache/*';
        $jsCachePath = BASE_PATH . '/public/assets/js/cache/*';

        try {
            $filesystem = new Filesystem();
            
            $filesystem->remove(glob($cachePath));

            $io->success('Flute cache have been deleted successfully.');

            $filesystem->remove(glob($proxiesPath));

            $io->success('Proxies cache have been deleted successfully.');

            $filesystem->remove(glob($cssCachePath));

            $io->success('CSS cache have been deleted successfully.');

            $filesystem->remove(glob($jsCachePath));

            $io->success('JS have been deleted successfully.');

            $filesystem->remove(glob($translationsPath));

            $io->success('Translations cache have been deleted successfully.');

            $filesystem->remove(glob($viewsPath));

            $io->success('Views cache have been deleted successfully.');

            return Command::SUCCESS;
        } catch (IOException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}
