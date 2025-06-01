<?php

namespace Flute\Core\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\ProgressBar;

class GenerateModuleCommand extends Command
{
    protected static $defaultName = 'generate:module';

    private array $componentTypes = [
        'controller' => '/Controllers',
        'model' => '/Models',
        'repository' => '/Repositories',
        'service' => '/Services',
        'widget' => '/Widgets',
        'event' => '/Events',
        'listener' => '/Listeners',
        'middleware' => '/Middleware',
        'extension' => '/Providers/Extensions',
    ];

    private array $adminComponentTypes = [
        'screen' => '/Screens',
        'service' => '/Services',
        'listener' => '/Listeners',
    ];

    protected function configure()
    {
        $this
            ->setName('generate:module')
            ->setDescription('Generates a new module structure.')
            ->setHelp('This command allows you to generate a new module structure with customizable components.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Flute CMS Module Generator');

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        // Step 1: Basic module information
        $io->section('Step 1: Basic Module Information');

        $moduleNameQuestion = new Question('Please enter the name of the module: ');
        $moduleNameQuestion->setValidator(function ($answer) {
            if (!preg_match('/^[a-zA-Z]+$/', $answer)) {
                throw new \RuntimeException(
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

        // Step 2: Module type
        $io->section('Step 2: Module Type');

        $moduleTypeQuestion = new ChoiceQuestion(
            'Select module type:',
            ['standard', 'admin'],
            'standard'
        );
        $moduleType = $helper->ask($input, $output, $moduleTypeQuestion);

        // Step 3: Module components
        $io->section('Step 3: Module Components');

        $translationsQuestion = new ConfirmationQuestion('Include translations? (y/n) [y]: ', true);
        $translations = $helper->ask($input, $output, $translationsQuestion);

        $installerQuestion = new ConfirmationQuestion('Include installer class? (y/n) [y]: ', true);
        $installer = $helper->ask($input, $output, $installerQuestion);

        $readmeQuestion = new ConfirmationQuestion('Generate README.md? (y/n) [y]: ', true);
        $readme = $helper->ask($input, $output, $readmeQuestion);

        // Routes are only needed for admin modules
        $routes = false;
        if ($moduleType === 'admin') {
            $routesQuestion = new ConfirmationQuestion('Generate routes file? (y/n) [y]: ', true);
            $routes = $helper->ask($input, $output, $routesQuestion);
        } else {
            $io->note('For standard modules, routes will be defined using annotations in controllers.');
        }

        $migrationQuestion = new ConfirmationQuestion('Generate migration file? (y/n) [n]: ', false);
        $migration = $helper->ask($input, $output, $migrationQuestion);

        // Step 4: Select Module Components
        $io->section('Step 4: Select Additional Components');

        $components = [];
        
        if ($moduleType === 'standard') {
            foreach ($this->componentTypes as $type => $dir) {
                $componentQuestion = new ConfirmationQuestion(
                    "Include $type component? (y/n) [n]: ",
                    false
                );
                if ($helper->ask($input, $output, $componentQuestion)) {
                    $components[] = $type;
                }
            }
        } else {
            foreach ($this->adminComponentTypes as $type => $dir) {
                $componentQuestion = new ConfirmationQuestion(
                    "Include $type component? (y/n) [n]: ",
                    false
                );
                if ($helper->ask($input, $output, $componentQuestion)) {
                    $components[] = $type;
                }
            }
        }

        // Step 5: Admin panel integration (for standard modules)
        $adminPanel = false;
        $adminMenuText = '';
        $adminMenuIcon = '';

        if ($moduleType === 'standard') {
            $io->section('Step 5: Admin Panel Integration');

            $adminPanelQuestion = new ConfirmationQuestion('Add Admin Panel menu item? (y/n) [y]: ', true);
            $adminPanel = $helper->ask($input, $output, $adminPanelQuestion);

            if ($adminPanel) {
                $adminMenuTextQuestion = new Question('Admin menu item text: ', $moduleName);
                $adminMenuText = $helper->ask($input, $output, $adminMenuTextQuestion);

                $adminMenuIconQuestion = new Question('Admin menu icon (FontAwesome class, e.g. fa-puzzle-piece): ', 'fa-puzzle-piece');
                $adminMenuIcon = $helper->ask($input, $output, $adminMenuIconQuestion);
            }
        } else {
            // For admin modules, we always need menu information
            $io->section('Step 5: Admin Package Configuration');

            $adminMenuTextQuestion = new Question('Admin menu item text: ', $moduleName);
            $adminMenuText = $helper->ask($input, $output, $adminMenuTextQuestion);

            $adminMenuIconQuestion = new Question('Admin menu icon (Phosphor icon, e.g. ph.bold.puzzle-piece-bold): ', 'ph.bold.puzzle-piece-bold');
            $adminMenuIcon = $helper->ask($input, $output, $adminMenuIconQuestion);
        }

        // Step 6: Frontend assets
        $io->section('Step 6: Frontend Assets');

        $stylesQuestion = new ConfirmationQuestion('Include SCSS structure? (y/n) [y]: ', true);
        $includeStyles = $helper->ask($input, $output, $stylesQuestion);

        $scriptsQuestion = new ConfirmationQuestion('Include JavaScript structure? (y/n) [y]: ', true);
        $includeScripts = $helper->ask($input, $output, $scriptsQuestion);

        // Create module structure
        return $this->createModuleStructure(
            $moduleName,
            $description,
            $author,
            $translations,
            $installer,
            $readme,
            $components,
            $adminPanel,
            $adminMenuText,
            $adminMenuIcon,
            $includeStyles,
            $includeScripts,
            $output,
            $io,
            $moduleType
        );
    }

    private function createModuleStructure(
        $moduleName,
        $description,
        $author,
        bool $translations,
        bool $installer,
        bool $readme,
        array $components,
        bool $adminPanel,
        string $adminMenuText,
        string $adminMenuIcon,
        bool $includeStyles,
        bool $includeScripts,
        OutputInterface $output,
        SymfonyStyle $io,
        string $moduleType = 'standard'
    ) {
        // For admin modules, create in Admin/Packages directory
        if ($moduleType === 'admin') {
            $baseDir = BASE_PATH . '/app/Core/Modules/Admin/Packages/' . $moduleName;
        } else {
            $baseDir = BASE_PATH . '/app/Modules/' . $moduleName;
        }

        if (file_exists($baseDir)) {
            $io->error("Module $moduleName already exists");
            return Command::FAILURE;
        }

        // Start progress display
        $io->section('Generating Module Structure');

        // Calculate total steps
        $totalSteps = count($components) + 5; // Base structure + files creation

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
            if ($moduleType === 'admin') {
                $directories[] = $this->adminComponentTypes[$component];
            } else {
                $directories[] = $this->componentTypes[$component];
            }
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
            $directories[] = '/Resources/assets/scss/components';
        }

        if ($includeScripts) {
            $directories[] = '/Resources/assets/js';
            $directories[] = '/Resources/assets/js/components';
        }

        // Create directories
        foreach ($directories as $dir) {
            $dirPath = $baseDir . $dir;
            if (!mkdir($dirPath, 0777, true) && !is_dir($dirPath)) {
                $io->error("Failed to create directory: $dirPath");
                return Command::FAILURE;
            }
        }

        $progressBar->advance();
        $progressBar->setMessage('Creating basic files...');

        // 2. Create base files
        if ($translations) {
            file_put_contents(
                $baseDir . '/Resources/lang/en/' . strtolower($moduleName) . '.php',
                "<?php\n\nreturn [\n    'module_name' => '$moduleName',\n    'description' => '$description'\n];"
            );
            file_put_contents(
                $baseDir . '/Resources/lang/ru/' . strtolower($moduleName) . '.php',
                "<?php\n\nreturn [\n    'module_name' => '$moduleName',\n    'description' => '$description'\n];"
            );
        }

        // Create index view
        if ($moduleType === 'admin') {
            file_put_contents(
                $baseDir . '/Resources/views/index.blade.php',
                $this->stubAdminView($moduleName)
            );
        } else {
            file_put_contents(
                $baseDir . '/Resources/views/index.blade.php',
                $this->stubView($moduleName)
            );
        }

        // Create service provider or admin package
        if ($moduleType === 'admin') {
            file_put_contents(
                $baseDir . '/' . $moduleName . 'Package.php',
                $this->stubAdminPackage($moduleName, $adminMenuText, $adminMenuIcon)
            );
            
            // Create routes file for admin package
            file_put_contents(
                $baseDir . '/routes.php',
                $this->stubAdminRoutes($moduleName)
            );
        } else {
            file_put_contents(
                $baseDir . '/Providers/' . $moduleName . 'Provider.php',
                $this->stubServiceProvider(
                    $moduleName,
                    $translations,
                    $adminPanel,
                    $adminMenuText,
                    $adminMenuIcon
                )
            );
            
            // We don't need to create a routes file for standard modules
            // as we'll use annotations in the controllers
        }

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

        // Create README if needed
        if ($readme) {
            file_put_contents(
                $baseDir . '/README.md',
                $this->stubReadme($moduleName, $description)
            );
        }

        $progressBar->advance();
        $progressBar->setMessage('Creating component files...');

        // 3. Create component files
        foreach ($components as $component) {
            if ($moduleType === 'admin') {
                $this->createAdminComponentFile($baseDir, $moduleName, $component);
            } else {
                $this->createComponentFile($baseDir, $moduleName, $component);
            }
            $progressBar->advance();
        }

        // 4. Create frontend files if needed
        if ($includeStyles) {
            if ($moduleType === 'admin') {
                $scssFileName = strtolower($moduleName) . '.scss';
                file_put_contents(
                    $baseDir . '/Resources/assets/scss/' . $scssFileName,
                    $this->stubScss($moduleName)
                );
            } else {
                file_put_contents(
                    $baseDir . '/Resources/assets/scss/main.scss',
                    $this->stubScss($moduleName)
                );
            }
        }

        if ($includeScripts) {
            if ($moduleType === 'admin') {
                $jsFileName = strtolower($moduleName) . '.js';
                file_put_contents(
                    $baseDir . '/Resources/assets/js/' . $jsFileName,
                    $this->stubJs($moduleName)
                );
            } else {
                file_put_contents(
                    $baseDir . '/Resources/assets/js/main.js',
                    $this->stubJs($moduleName)
                );
            }
        }

        $progressBar->advance();
        $progressBar->finish();

        $io->newLine(2);
        $io->success("Module structure generated for '$moduleName'");
        
        if ($moduleType === 'admin') {
            $io->text([
                "Admin package location: <info>app/Core/Modules/Admin/Packages/$moduleName</info>",
                "To use this package, register it in your application using: <info>app()->make('admin')->registerPackage(app(Flute\\Admin\\Packages\\$moduleName\\{$moduleName}Package::class));</info>"
            ]);
        } else {
            $io->text([
                "Module location: <info>app/Modules/$moduleName</info>",
                "To enable this module, run: <info>php flute module:enable $moduleName</info>"
            ]);
        }

        return Command::SUCCESS;
    }

    private function createComponentFile($baseDir, $moduleName, $component)
    {
        $dir = $this->componentTypes[$component];
        $className = ucfirst($component);

        // Special case for extension components
        if ($component === 'extension') {
            $content = $this->stubExtension($moduleName);
            file_put_contents(
                $baseDir . $dir . '/' . $moduleName . 'Extension.php',
                $content
            );
            return;
        }

        // Special case for middleware
        if ($component === 'middleware') {
            $content = $this->stubMiddleware($moduleName);
            file_put_contents(
                $baseDir . $dir . '/' . $moduleName . 'Middleware.php',
                $content
            );
            return;
        }

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

        // For event component
        if ($component === 'event') {
            $content = file_get_contents($this->getStubPath('event'));
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
        
        // Special case for service - don't append Service to the class name
        if ($component === 'service') {
            $content = file_get_contents($this->getStubPath('service'));
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

        // For controllers, create one with example methods
        if ($component === 'controller') {
            $content = $this->stubController($moduleName);
            file_put_contents(
                $baseDir . $dir . '/' . $moduleName . 'Controller.php',
                $content
            );
            return;
        }

        // For repositories
        if ($component === 'repository') {
            $content = file_get_contents($this->getStubPath('repository'));
            $content = str_replace(
                ['{{MODULE_NAME}}', '{{MODULE_NAME_LOWER}}'],
                [$moduleName, strtolower($moduleName)],
                $content
            );
            file_put_contents(
                $baseDir . $dir . '/' . $moduleName . 'Repository.php',
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

        // For listeners
        if ($component === 'listener') {
            $content = file_get_contents($this->getStubPath('listener'));
            $content = str_replace(
                ['{{MODULE_NAME}}', '{{MODULE_NAME_LOWER}}'],
                [$moduleName, strtolower($moduleName)],
                $content
            );
            file_put_contents(
                $baseDir . $dir . '/' . $moduleName . 'Listener.php',
                $content
            );
            return;
        }

        // Default component stub
        $content = $this->stubComponent($moduleName, $component, $className);
        file_put_contents(
            $baseDir . $dir . '/' . $moduleName . $className . '.php',
            $content
        );
    }

    private function createAdminComponentFile($baseDir, $moduleName, $component)
    {
        $dir = $this->adminComponentTypes[$component];
        
        // Special case for screens
        if ($component === 'screen') {
            $content = file_get_contents($this->getStubPath('admin-screen'));
            $content = str_replace(
                ['{{MODULE_NAME}}', '{{MODULE_NAME_LOWER}}'],
                [$moduleName, strtolower($moduleName)],
                $content
            );
            file_put_contents(
                $baseDir . $dir . '/' . $moduleName . 'Screen.php',
                $content
            );
            return;
        }
        
        // Special case for services - don't append Service to the class name
        if ($component === 'service') {
            $content = file_get_contents($this->getStubPath('service'));
            $content = str_replace(
                ['{{MODULE_NAME}}'],
                [$moduleName],
                $content
            );
            
            // Update namespace for admin package
            $content = str_replace(
                'namespace Flute\Modules\{{MODULE_NAME}}\Services;',
                'namespace Flute\Admin\Packages\\' . $moduleName . '\Services;',
                $content
            );
            
            file_put_contents(
                $baseDir . $dir . '/' . $moduleName . '.php',
                $content
            );
            return;
        }
        
        // Special case for listeners
        if ($component === 'listener') {
            $content = file_get_contents($this->getStubPath('listener'));
            $content = str_replace(
                ['{{MODULE_NAME}}'],
                [$moduleName],
                $content
            );
            
            // Update namespace for admin package
            $content = str_replace(
                'namespace Flute\Modules\{{MODULE_NAME}}\Listeners;',
                'namespace Flute\Admin\Packages\\' . $moduleName . '\Listeners;',
                $content
            );
            
            file_put_contents(
                $baseDir . $dir . '/' . $moduleName . 'Listener.php',
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

    private function stubServiceProvider($name, bool $needTranslations = false, bool $adminPanel = false, string $menuText = '', string $menuIcon = '')
    {
        $stubFile = file_get_contents($this->getStubPath('modulesp'));

        $replacements = [
            '{{MODULE_NAME}}' => $name,
            '{{TRANSLATES}}' => $needTranslations ? '$this->loadTranslations();' : '',
            '<CURRENT_CURSOR_POSITION>' => ''
        ];

        // Add admin panel menu registration if needed
        if ($adminPanel) {
            $adminCode = "        // Register admin menu item\n";
            $adminCode .= "        \$this->app->make('admin')->addMenuItem([\n";
            $adminCode .= "            'title' => '$menuText',\n";
            $adminCode .= "            'route' => 'admin.modules.$name.index',\n";
            $adminCode .= "            'icon' => '$menuIcon',\n";
            $adminCode .= "            'order' => 100\n";
            $adminCode .= "        ]);\n\n";

            $replacements['<CURRENT_CURSOR_POSITION>'] = $adminCode;
        }

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
                '{{MODULE_AUTHOR}}'
            ],
            [
                $name,
                $description,
                $author
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
                    "Flute\\Modules\\$name\\" => ""
                ]
            ],
            'authors' => [
                [
                    'name' => $author
                ]
            ],
            'minimum-stability' => 'dev'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    private function stubReadme($name, $description)
    {
        return "# $name Module\n\n$description\n\n## Installation\n\n```bash\nphp flute module:enable $name\n```\n\n## Usage\n\nDescribe how to use the module here.\n\n## Configuration\n\nDescribe configuration options here.\n";
    }

    private function stubView($name)
    {
        return str_replace(
            ['{{MODULE_NAME}}', '{{MODULE_NAME_LOWER}}'],
            [$name, strtolower($name)],
            file_get_contents($this->getStubPath('view'))
        );
    }

    private function stubComponent($name, $component, $className)
    {
        $namespace = "Flute\\Modules\\$name\\" . ucfirst($component === 'extension' ? 'Providers\\Extensions' : (substr($this->componentTypes[$component], 1)));

        return "<?php\n\nnamespace $namespace;\n\nclass $name$className\n{\n    // TODO: Implement $name$className\n}\n";
    }

    private function stubController($name)
    {
        $stubFile = file_get_contents($this->getStubPath('controller'));
        return str_replace(
            ['{{MODULE_NAME}}', '{{MODULE_NAME_LOWER}}'],
            [$name, strtolower($name)],
            $stubFile
        );
    }

    private function stubExtension($name)
    {
        $stubFile = file_get_contents($this->getStubPath('extension'));
        return str_replace('{{MODULE_NAME}}', $name, $stubFile);
    }

    private function stubMiddleware($name)
    {
        $stubFile = file_get_contents($this->getStubPath('middleware'));
        return str_replace('{{MODULE_NAME}}', $name, $stubFile);
    }

    private function stubScss($name)
    {
        $stubFile = file_get_contents($this->getStubPath('scss'));
        $modulePrefix = strtolower($name);
        return str_replace(['{{MODULE_NAME}}', '{{MODULE_PREFIX}}'], [$name, $modulePrefix], $stubFile);
    }

    private function stubJs($name)
    {
        $stubFile = file_get_contents($this->getStubPath('js'));
        return str_replace('{{MODULE_NAME}}', $name, $stubFile);
    }

    private function stubAdminPackage($name, $menuText, $menuIcon)
    {
        $stubFile = file_get_contents($this->getStubPath('admin-package'));
        $nameLower = strtolower($name);
        
        return str_replace(
            [
                '{{MODULE_NAME}}',
                '{{MODULE_NAME_LOWER}}',
                '{{MENU_ICON}}'
            ],
            [
                $name,
                $nameLower,
                $menuIcon
            ],
            $stubFile
        );
    }

    private function stubAdminRoutes($name)
    {
        $stubFile = file_get_contents($this->getStubPath('admin-routes'));
        $nameLower = strtolower($name);
        
        return str_replace(
            [
                '{{MODULE_NAME}}',
                '{{MODULE_NAME_LOWER}}'
            ],
            [
                $name,
                $nameLower
            ],
            $stubFile
        );
    }

    private function stubAdminView($name)
    {
        $stubFile = file_get_contents($this->getStubPath('admin-view'));
        $nameLower = strtolower($name);
        
        return str_replace(
            [
                '{{MODULE_NAME}}',
                '{{MODULE_NAME_LOWER}}'
            ],
            [
                $name,
                $nameLower
            ],
            $stubFile
        );
    }
}
