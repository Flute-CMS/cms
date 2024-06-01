<?php

namespace Flute\Core\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenerateModuleCommand extends Command
{
    protected static $defaultName = 'generate:module';

    protected function configure()
    {
        $this
            ->setDescription('Generates a new module structure.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $helper = $this->getHelper('question');
        $moduleNameQuestion = new Question('Please enter the name of the module: ');
        $moduleNameQuestion->setValidator(function ($answer) {
            if (!preg_match('/^[a-zA-Z]+$/', $answer)) {
                throw new \RuntimeException(
                    'The module name must contain only English letters without spaces.'
                );
            }
            return $answer;
        });
        $moduleName = $helper->ask($input, $output, $moduleNameQuestion);

        $translationsQuestion = new ChoiceQuestion(
            'Do you need to create translations in the module? (yes/no): ',
            ['yes', 'no'],
            0
        );
        $translationsQuestion->setErrorMessage('Answer %s is invalid');
        $translations = $helper->ask($input, $output, $translationsQuestion);

        $installerQuestion = new ChoiceQuestion(
            'Do you need to create an install class? (yes/no): ',
            ['yes', 'no'],
            0
        );
        $installerQuestion->setErrorMessage('Answer %s is invalid');
        $installer = $helper->ask($input, $output, $installerQuestion);

        return $this->createModuleStructure($moduleName, $translations === 'yes', $installer === 'yes', $output, $io);
    }

    private function createModuleStructure($moduleName, bool $translations, bool $installer, OutputInterface $output, SymfonyStyle $io)
    {
        $baseDir = BASE_PATH . '/app/Modules/' . $moduleName;

        if (file_exists($baseDir)) {
            $io->error("Module $moduleName already exists");
            return Command::FAILURE;
        }

        $directories = [
            '/Resources/assets/js',
            '/Resources/assets/styles',
            '/Resources/views',
            '/ServiceProviders/Extensions',
            '/Services',
            '/Widgets'
        ];

        if ($translations) {
            $directories = array_merge([
                '/i18n/en',
                '/i18n/ru',
            ], $directories);
        }

        foreach ($directories as $dir) {
            $dirPath = $baseDir . $dir;
            if (!mkdir($dirPath, 0777, true) && !is_dir($dirPath)) {
                $io->error("Failed to create directory: $dirPath");
                return Command::FAILURE;
            }
        }

        // Create Files
        if ($translations) {
            file_put_contents($baseDir . '/i18n/en/' . strtolower($moduleName) . '.php', "<?php\n\nreturn [];");
            file_put_contents($baseDir . '/i18n/ru/' . strtolower($moduleName) . '.php', "<?php\n\nreturn [];");
        }

        file_put_contents($baseDir . '/Resources/views/index.blade.php', "<!-- Blade Template -->");
        file_put_contents($baseDir . '/ServiceProviders/' . $moduleName . 'ServiceProvider.php', $this->stubServiceProvider($moduleName, $translations));

        if ($installer) {
            file_put_contents($baseDir . '/Installer.php', $this->stubInstaller($moduleName));
        }
        file_put_contents($baseDir . '/module.json', $this->stubJson($moduleName));

        $io->success("Module structure generated for '$moduleName'");

        return Command::SUCCESS;
    }

    private function stubServiceProvider($name, bool $needTranslations = false)
    {
        return str_replace(['{{MODULE_NAME}}', '{{TRANSLATES}}'], [$name, $needTranslations ? '$this->loadTranslations();' : ''], file_get_contents(BASE_PATH . '/storage/app/stubs/modulesp.stub'));
    }

    private function stubInstaller($name)
    {
        return str_replace('{{MODULE_NAME}}', $name, file_get_contents(BASE_PATH . '/storage/app/stubs/moduleinstaller.stub'));
    }

    private function stubJson($name)
    {
        return str_replace('{{MODULE_NAME}}', $name, file_get_contents(BASE_PATH . '/storage/app/stubs/modulejson.stub'));
    }
}
