<?php

namespace Flute\Core\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class LogsClearCommand extends Command
{
    protected static $defaultName = 'logs:clear';

    protected function configure()
    {
        $this
            ->setName('logs:clear')
            ->setDescription('Clears the logs in storage/logs.')
            ->setHelp('This command allows you to clear the all Flute logs.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $folderPath = BASE_PATH . '/storage/logs/*.log';

        try {
            $filesystem = new Filesystem();
            $filesystem->remove(glob($folderPath));

            $io->success('Logs have been deleted successfully.');

            return Command::SUCCESS;
        } catch (IOException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}
