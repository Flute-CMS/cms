<?php

namespace Flute\Core\Console\Command;

use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenerateModuleCommand extends Command
{
    protected static $defaultName = 'generate:module';

    private array $componentTypes = [
        'model' => '/database/Entities',
        'widget' => '/Widgets',
    ];

    protected function configure()
    {
        $this
            ->setName('generate:module')
            ->setDescription('Generates a new module structure.')
            ->setHelp('This command allows you to generate a new module structure with basic components.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Flute CMS Module Generator');

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        // Step 1: Basic module information
        $io->section('Basic Module Information');

        $moduleNameQuestion = new Question('Please enter the name of the module: ');
        $moduleNameQuestion->setValidator(static function ($answer) {
            if (!preg_match('/^[a-zA-Z]+$/', $answer)) {
                throw new RuntimeException(
                    'The module name must contain only English letters without spaces.'
                );
            }

            return ucfirst($answer);
        });
        $moduleName = $helper->ask($input, $output, $moduleNameQuestion);

        $descriptionQuestion = new Question('Module description: ', 'A Flute CMS module');
        $description = $helper->ask($input, $output, $descriptionQuestion);

        $authorQuestion = new Question('Module author: ', 'Flute Developer');
        $author = $helper->ask($input, $output, $authorQuestion);

        // Step 2: Module components
        $io->section('Module Components');

        $translationsQuestion = new ConfirmationQuestion('Include translations? (y/n) [y]: ', true);
        $translations = $helper->ask($input, $output, $translationsQuestion);

        $installerQuestion = new ConfirmationQuestion('Include installer class? (y/n) [y]: ', true);
        $installer = $helper->ask($input, $output, $installerQuestion);

        // Step 3: Select Module Components
        $io->section('Select Additional Components');

        $components = [];
        foreach ($this->componentTypes as $type => $dir) {
            $componentQuestion = new ConfirmationQuestion(
                "Include {$type} component? (y/n) [n]: ",
                false
            );
            if ($helper->ask($input, $output, $componentQuestion)) {
                $components[] = $type;
            }
        }

        // Step 4: Frontend assets
        $io->section('Frontend Assets');

        $stylesQuestion = new ConfirmationQuestion('Include SCSS structure? (y/n) [n]: ', false);
        $includeStyles = $helper->ask($input, $output, $stylesQuestion);

        $scriptsQuestion = new ConfirmationQuestion('Include JavaScript structure? (y/n) [n]: ', false);
        $includeScripts = $helper->ask($input, $output, $scriptsQuestion);

        // Create module structure
        return $this->createModuleStructure(
            $moduleName,
            $description,
            $author,
            $translations,
            $installer,
            $components,
            $includeStyles,
            $includeScripts,
            $output,
            $io
        );
    }

    private function createModuleStructure(
        $moduleName,
        $description,
        $author,
        bool $translations,
        bool $installer,
        array $components,
        bool $includeStyles,
        bool $includeScripts,
        OutputInterface $output,
        SymfonyStyle $io
    ) {
        $baseDir = BASE_PATH . '/app/Modules/' . $moduleName;

        if (file_exists($baseDir)) {
            $io->error("Module {$moduleName} already exists");

            return Command::FAILURE;
        }

        // Start progress display
        $io->section('Generating Module Structure');

        // Calculate total steps
        $totalSteps = count($components) + 4; // Base structure + files creation

        $progressBar = new ProgressBar($output, $totalSteps);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        $progressBar->setMessage('Creating directory structure...');
        $progressBar->start();

        // 1. Create base directories
        $directories = [
            '/Resources/views',
            '/Providers',
        ];

        // Add component directories
        foreach ($components as $component) {
            $directories[] = $this->componentTypes[$component];
        }

        // Add translation directories if needed
        if ($translations) {
            $directories = array_merge([
                '/Resources/lang/en',
                '/Resources/lang/ru',
            ], $directories);
        }

        // Add frontend asset directories if needed
        if ($includeStyles) {
            $directories[] = '/Resources/assets/scss';
        }

        if ($includeScripts) {
            $directories[] = '/Resources/assets/js';
        }

        // Create directories
        foreach ($directories as $dir) {
            $dirPath = $baseDir . $dir;
            if (!mkdir($dirPath, 0o777, true) && !is_dir($dirPath)) {
                $io->error("Failed to create directory: {$dirPath}");

                return Command::FAILURE;
            }
        }

        $progressBar->advance();
        $progressBar->setMessage('Creating basic files...');

        // 2. Create base files
        if ($translations) {
            file_put_contents(
                $baseDir . '/Resources/lang/en/' . strtolower($moduleName) . '.php',
                "<?php\n\nreturn [\n    'module_name' => '{$moduleName}',\n    'description' => '{$description}'\n];"
            );
            file_put_contents(
                $baseDir . '/Resources/lang/ru/' . strtolower($moduleName) . '.php',
                "<?php\n\nreturn [\n    'module_name' => '{$moduleName}',\n    'description' => '{$description}'\n];"
            );
        }

        // Create index view
        file_put_contents(
            $baseDir . '/Resources/views/index.blade.php',
            $this->stubView($moduleName)
        );

        // Create service provider
        file_put_contents(
            $baseDir . '/Providers/' . $moduleName . 'Provider.php',
            $this->stubServiceProvider($moduleName, $translations)
        );

        $progressBar->advance();
        $progressBar->setMessage('Creating configuration files...');

        // Create installer if needed
        if ($installer) {
            file_put_contents(
                $baseDir . '/Installer.php',
                $this->stubInstaller($moduleName)
            );
        }

        // Create module.json
        file_put_contents(
            $baseDir . '/module.json',
            $this->stubJson($moduleName, $description, $author)
        );

        // Create composer.json
        file_put_contents(
            $baseDir . '/composer.json',
            $this->stubComposerJson($moduleName, $description, $author)
        );

        $progressBar->advance();
        $progressBar->setMessage('Creating component files...');

        // 3. Create component files
        foreach ($components as $component) {
            $this->createComponentFile($baseDir, $moduleName, $component);
            $progressBar->advance();
        }

        // 4. Create frontend files if needed
        if ($includeStyles) {
            file_put_contents(
                $baseDir . '/Resources/assets/scss/main.scss',
                $this->stubScss($moduleName)
            );
        }

        if ($includeScripts) {
            file_put_contents(
                $baseDir . '/Resources/assets/js/main.js',
                $this->stubJs($moduleName)
            );
        }

        $progressBar->finish();

        $io->newLine(2);
        $io->success("Module structure generated for '{$moduleName}'");
        $io->text([
            "Module location: <info>app/Modules/{$moduleName}</info>",
            "To enable this module, run: <info>php flute module:enable {$moduleName}</info>",
        ]);

        return Command::SUCCESS;
    }

    private function createComponentFile($baseDir, $moduleName, $component)
    {
        $dir = $this->componentTypes[$component];

        // For model component, use Cycle ORM
        if ($component === 'model') {
            $content = file_get_contents($this->getStubPath('cycle-model'));
            $content = str_replace(
                ['{{MODULE_NAME}}', '{{MODULE_NAME_LOWER}}'],
                [$moduleName, strtolower($moduleName)],
                $content
            );
            file_put_contents(
                $baseDir . $dir . '/' . $moduleName . '.php',
                $content
            );

            return;
        }

        // For widgets
        if ($component === 'widget') {
            $content = file_get_contents($this->getStubPath('widget'));
            $content = str_replace(
                ['{{MODULE_NAME}}', '{{MODULE_NAME_LOWER}}'],
                [$moduleName, strtolower($moduleName)],
                $content
            );
            file_put_contents(
                $baseDir . $dir . '/' . $moduleName . 'Widget.php',
                $content
            );

            return;
        }
    }

    private function getStubPath(string $stubName): string
    {
        $stubsDir = BASE_PATH . '/@stubs';
        $fallbackStubsDir = BASE_PATH . '/storage/app/stubs';

        $stubPath = $stubsDir . '/' . $stubName . '.stub';

        if (file_exists($stubPath)) {
            return $stubPath;
        }

        return $fallbackStubsDir . '/' . $stubName . '.stub';
    }

    private function stubServiceProvider($name, bool $needTranslations = false)
    {
        $stubFile = file_get_contents($this->getStubPath('modulesp'));

        $replacements = [
            '{{MODULE_NAME}}' => $name,
            '{{TRANSLATES}}' => $needTranslations ? '$this->loadTranslations();' : '',
            '<CURRENT_CURSOR_POSITION>' => '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $stubFile);
    }

    private function stubInstaller($name)
    {
        return str_replace('{{MODULE_NAME}}', $name, file_get_contents($this->getStubPath('moduleinstaller')));
    }

    private function stubJson($name, $description, $author)
    {
        $stubFile = file_get_contents($this->getStubPath('modulejson'));

        return str_replace(
            [
                '{{MODULE_NAME}}',
                '{{MODULE_DESCRIPTION}}',
                '{{MODULE_AUTHOR}}',
            ],
            [
                $name,
                $description,
                $author,
            ],
            $stubFile
        );
    }

    private function stubComposerJson($name, $description, $author)
    {
        return json_encode([
            'name' => 'flute/module-' . strtolower($name),
            'description' => $description,
            'type' => 'flute-module',
            'require' => [],
            'autoload' => [
                'psr-4' => [
                    "Flute\\Modules\\{$name}\\" => "",
                ],
            ],
            'authors' => [
                [
                    'name' => $author,
                ],
            ],
            'minimum-stability' => 'dev',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    private function stubView($name)
    {
        return str_replace(
            ['{{MODULE_NAME}}', '{{MODULE_NAME_LOWER}}'],
            [$name, strtolower($name)],
            file_get_contents($this->getStubPath('view'))
        );
    }

    private function stubScss($name)
    {
        return "/* {$name} Module Styles */\n\n.{strtolower({$name})}-module {\n    // Add your styles here\n}\n";
    }

    private function stubJs($name)
    {
        return "// {$name} Module JavaScript\n\nconsole.log('{$name} module loaded');\n";
    }
}
