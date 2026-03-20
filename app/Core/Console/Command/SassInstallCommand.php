<?php

namespace Flute\Core\Console\Command;

use Flute\Core\Template\NativeSassCompiler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SassInstallCommand extends Command
{
    protected static $defaultName = 'sass:install';

    protected function configure()
    {
        $this
            ->setName('sass:install')
            ->setDescription('Download the dart-sass binary for the current platform.')
            ->addOption(
                'version',
                null,
                InputOption::VALUE_REQUIRED,
                'Specific dart-sass version to download.',
                '1.98.0',
            )
            ->setHelp(
                'Downloads the dart-sass standalone binary for fast SCSS compilation. Falls back to scssphp if unavailable.',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $version = $input->getOption('version');

        $io->title('Installing dart-sass ' . $version);

        $compiler = new NativeSassCompiler();
        if ($compiler->isNativeAvailable()) {
            $io->success('dart-sass is already available on this system.');

            return Command::SUCCESS;
        }

        $io->text('Downloading dart-sass binary for ' . PHP_OS_FAMILY . ' ' . php_uname('m') . '...');

        $success = NativeSassCompiler::downloadBinary($version);

        if ($success) {
            $io->success('dart-sass ' . $version . ' installed successfully!');
            $io->text('SCSS compilation will now use the native binary (~50-100x faster).');

            return Command::SUCCESS;
        }

        $io->warning('Could not download dart-sass binary. SCSS compilation will use scssphp (pure PHP fallback).');
        $io->text('Possible reasons: no internet access, unsupported platform, or restricted filesystem.');

        return Command::FAILURE;
    }
}
