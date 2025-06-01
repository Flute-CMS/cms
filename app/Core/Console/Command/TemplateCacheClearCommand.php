<?php

namespace Flute\Core\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class TemplateCacheClearCommand extends Command
{
    protected static $defaultName = 'template:clear';

    protected function configure()
    {
        $this
            ->setName('template:clear')
            ->setDescription('Clears the template cache.')
            ->setHelp('This command allows you to clear the template cache.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new SymfonyStyle($input, $output);

        $folderPath = BASE_PATH . '/storage/app/views/*';

        try {
            $filesystem = new Filesystem();
            $filesystem->remove(glob($folderPath));

            $io->success('Template cache have been deleted successfully.');

            return Command::SUCCESS;
        } catch (IOException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}
