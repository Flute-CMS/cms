<?php

namespace Flute\Core\Console\Command;

use Flute\Core\Services\CacheWarmupService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CacheWarmupCommand extends Command
{
    protected static $defaultName = 'cache:warmup';

    protected static $defaultDescription = 'Warms up caches (modules, routes, ORM schema) for CRON mode';

    public function __construct(private CacheWarmupService $warmupService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->writeln('<info>Warming up caches...</info>');

        $this->warmupService->warmup();

        $io->success('Warmup completed.');

        return Command::SUCCESS;
    }
}
