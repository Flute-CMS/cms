<?php

namespace Flute\Core\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenerateMigrationCommand extends Command
{
    protected static $defaultName = 'generate:migration';

    protected function configure()
    {
        $this
            ->setName('generate:migration')
            ->setDescription('Creates a new migration file.')
            ->setHelp('This command allows you to create a migration file...');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $modules = $this->getModules();

        if (empty($modules)) {
            $io->error("No modules found in app/Modules.");

            return Command::FAILURE;
        }

        $moduleQuestion = new ChoiceQuestion('Please select the module: ', array_values($modules));
        $module = $helper->ask($input, $output, $moduleQuestion);

        $nameQuestion = new Question('Please enter the name of the migration: ');
        $nameQuestion->setValidator(function ($answer) {
            if (!preg_match('/^[a-zA-Z_]+$/', $answer)) {
                throw new \RuntimeException(
                    'The migration name must contain only English letters and underscores without spaces.'
                );
            }

            return $answer;
        });
        $name = $helper->ask($input, $output, $nameQuestion);

        $directory = BASE_PATH . '/app/Modules/' . $modules[$module] . '/database/migrations/';
        $dateTime = new \DateTime();
        $formattedDate = $dateTime->format('Ymd');
        $formattedTime = $dateTime->format('His');
        $fileName = "{$formattedDate}.{$formattedTime}_0_{$name}.php";
        $filePath = $directory . $fileName;

        $migrationContent = $this->getMigrationStubContent($name);
        file_put_contents($filePath, $migrationContent);

        $io->success('Migration created: ' . $filePath);

        return Command::SUCCESS;
    }

    private function getModules()
    {
        $modulesDir = BASE_PATH . '/app/Modules';
        $modules = [];

        if (is_dir($modulesDir)) {
            foreach (new \DirectoryIterator($modulesDir) as $fileInfo) {
                if ($fileInfo->isDir() && !$fileInfo->isDot()) {
                    $modules[$fileInfo->getFilename()] = $fileInfo->getFilename();
                }
            }
        }

        return $modules;
    }

    private function getMigrationStubContent($name)
    {
        return sprintf(file_get_contents(BASE_PATH . '/storage/app/stubs/migration.stub'), $name);
    }
}
