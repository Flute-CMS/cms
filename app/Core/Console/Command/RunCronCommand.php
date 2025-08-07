<?php

namespace Flute\Core\Console\Command;

use GO\Scheduler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunCronCommand extends Command
{
    protected static $defaultName = 'cron:run';
    protected static $defaultDescription = 'Runs all registered cron tasks';

    /**
     * @var Scheduler
     */
    private $scheduler;

    public function __construct(Scheduler $scheduler)
    {
        parent::__construct();
        $this->scheduler = $scheduler;
    }

    protected function configure(): void
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription(self::$defaultDescription)
            ->setHelp('This command runs all registered cron tasks.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Running cron tasks...</info>');

        try {
            $this->scheduler->run();

            $output->writeln('<info>All cron tasks completed successfully.</info>');
            logs('cron')->info('All cron tasks completed successfully.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error running cron tasks: ' . $e->getMessage() . '</error>');
            logs('cron')->error($e);

            return Command::FAILURE;
        }
    }
}
