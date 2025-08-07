<?php

namespace Flute\Core\Console\Command;

use Flute\Core\Services\LoggerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LogsCleanupCommand extends Command
{
    protected LoggerService $loggerService;

    public function __construct(LoggerService $loggerService)
    {
        parent::__construct();
        $this->loggerService = $loggerService;
    }

    protected function configure()
    {
        $this
            ->setName('logs:cleanup')
            ->setDescription('Clean up old log files and archive logs')
            ->setHelp('This command will clean up old log files and archive logs');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Cleaning up old log files...</info>');

        try {
            $this->loggerService->cleanupOldLogs();
            $output->writeln('<info>Log files have been cleaned up successfully.</info>');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error cleaning up log files: ' . $e->getMessage() . '</error>');

            return Command::FAILURE;
        }
    }
}
