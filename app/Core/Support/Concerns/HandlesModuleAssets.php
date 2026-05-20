<?php

namespace Flute\Core\Support\Concerns;

use Flute\Core\Modules\Page\Services\WidgetManager;
use Flute\Core\Modules\Page\Widgets\Contracts\WidgetInterface;
use Flute\Core\Template\TemplateAssets;
use InvalidArgumentException;
use Throwable;

trait HandlesModuleAssets
{
    /**
     * @throws InvalidArgumentException
     */
    public function loadScss(string $assetsFile): void
    {
        $fullPath = $this->getModulePath($assetsFile);

        if (!file_exists($fullPath)) {
            throw new InvalidArgumentException("Assets file does not exist: {$fullPath}");
        }

        $templateAssets = app(TemplateAssets::class);
        $templateAssets->addScssFile($fullPath, 'main');
        $templateAssets->addImportPath(dirname($fullPath), 'main');
    }

    public function loadWidgets(): void
    {
        $widgetsCacheKey = "module.{$this->getModuleName()}.widgets_loaded";
        if (isset($this->loadedStatus[$widgetsCacheKey])) {
            return;
        }

        $widgetsDirectory = $this->getModulePath('Widgets');

        if (!is_dir($widgetsDirectory)) {
            return;
        }

        $moduleNamespace = "Flute\\Modules\\{$this->getModuleName()}\\Widgets";
        $widgets = [];

        $this->scanForWidgets($widgetsDirectory, $moduleNamespace, $widgets);

        if (!empty($widgets)) {
            $this->registerWidgets($widgets);
            $this->loadedStatus[$widgetsCacheKey] = true;
        }
    }

    public function registerWidgets(array $widgets): void
    {
        $widgetManager = app(WidgetManager::class);

        foreach ($widgets as $key => $widget) {
            try {
                $widgetManager->registerWidget($key, $widget);
            } catch (\Throwable $e) {
                logs('modules')->error("Error registering widget {$key}: " . $e->getMessage());
            }
        }
    }

    public function loadWidget(string $name, string $class)
    {
        try {
            if (!class_exists($class) && class_exists($name)) {
                [$name, $class] = [$class, $name];
            }

            app(WidgetManager::class)->registerWidget($name, $class);
        } catch (\Throwable $e) {
            logs('modules')->error("Error registering widget {$name}: " . $e->getMessage());
        }
    }

    public function loadComponents(): void
    {
        $componentsCacheKey = "module.{$this->getModuleName()}.components_loaded";
        if (isset($this->loadedStatus[$componentsCacheKey])) {
            return;
        }

        $componentsDirectory = $this->getModulePath('Components');

        if (!is_dir($componentsDirectory)) {
            return;
        }

        $moduleNamespace = "Flute\\Modules\\{$this->getModuleName()}\\Components";

        $components = [];

        $this->scanForComponents($componentsDirectory, $moduleNamespace, $components);

        if (!empty($components)) {
            $this->registerComponents($components);
            $this->loadedStatus[$componentsCacheKey] = true;
        }
    }

    public function registerComponents(array $components): void
    {
        try {
            if (class_exists('Clickfwd\\Yoyo\\Yoyo')) {
                \Clickfwd\Yoyo\Yoyo::registerComponents($components);
            }
        } catch (\Throwable $e) {
            if (is_debug()) {
                throw $e;
            }

            logs('modules')->error('Failed to register components: ' . $e->getMessage());
        }
    }

    public function registerNotificationTemplates(\Flute\Core\Modules\Notifications\Contracts\NotificationTemplateProviderInterface $provider): void
    {
        if (!function_exists('notification_templates')) {
            logs('modules')->warning(
                'notification_templates() helper not available for module: ' . $this->getModuleName(),
            );

            return;
        }

        try {
            notification_templates()->registerProvider($provider);
        } catch (Throwable $e) {
            logs('modules')->error(
                'Failed to register notification templates for ' . $this->getModuleName() . ': ' . $e->getMessage(),
            );
        }
    }

    protected function scanForWidgets(string $directory, string $namespace, array &$widgets): void
    {
        $moduleName = $this->getModuleName();
        $cacheKey = "module.{$moduleName}.widgets." . md5($directory . $namespace);

        cache()->tagKey("module.{$moduleName}", $cacheKey);

        $cachedWidgets = cache()->get($cacheKey);
        if ($cachedWidgets !== null) {
            $widgets = array_merge($widgets, $cachedWidgets);

            return;
        }

        $foundWidgets = [];
        foreach ($this->getFilesFromDirectory($directory) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $className = pathinfo($file->getBasename(), PATHINFO_FILENAME);
            $fullyQualifiedClassName = $namespace . '\\' . $className;

            $fullPath = $file->getPathname();
            if (is_file($fullPath)) {
                @require_once $fullPath;
            }

            if (!is_subclass_of($fullyQualifiedClassName, WidgetInterface::class, true)) {
                continue;
            }

            if (!isset($foundWidgets[$className])) {
                $foundWidgets[$className] = $fullyQualifiedClassName;
            }

            $base = preg_replace('/Widget$/', '', $className);
            if ($base !== '') {
                if (!isset($foundWidgets[$base])) {
                    $foundWidgets[$base] = $fullyQualifiedClassName;
                }

                $kebab = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $base));
                if ($kebab && !isset($foundWidgets[$kebab])) {
                    $foundWidgets[$kebab] = $fullyQualifiedClassName;
                }

                $kebabUcFirst = ucfirst($kebab);
                if ($kebabUcFirst && !isset($foundWidgets[$kebabUcFirst])) {
                    $foundWidgets[$kebabUcFirst] = $fullyQualifiedClassName;
                }
            }
        }

        if (!empty($foundWidgets)) {
            cache()->set($cacheKey, $foundWidgets, $this->defaultCacheDuration);
        }

        $widgets = array_merge($widgets, $foundWidgets);
    }

    protected function scanForComponents(string $directory, string $namespace, array &$components): void
    {
        $moduleName = $this->getModuleName();
        $cacheKey = "module.{$moduleName}.components." . md5($directory . $namespace);

        cache()->tagKey("module.{$moduleName}", $cacheKey);

        $cachedComponents = cache()->get($cacheKey);
        if ($cachedComponents !== null) {
            $components = array_merge($components, $cachedComponents);

            return;
        }

        $foundComponents = [];
        foreach ($this->getFilesFromDirectory($directory) as $file) {
            if ($file->isDir()) {
                $this->scanForComponents(
                    $file->getPathname(),
                    $namespace . '\\' . $file->getBasename(),
                    $foundComponents,
                );

                continue;
            }

            if ($file->getExtension() !== 'php') {
                continue;
            }

            $className = pathinfo($file->getBasename(), PATHINFO_FILENAME);
            $fullyQualifiedClassName = $namespace . '\\' . $className;

            if (!class_exists($fullyQualifiedClassName)) {
                continue;
            }

            $componentName = $this->kebabCase($className);
            $foundComponents[$componentName] = $fullyQualifiedClassName;
        }

        if (!empty($foundComponents)) {
            cache()->set($cacheKey, $foundComponents, $this->defaultCacheDuration);
        }

        $components = array_merge($components, $foundComponents);
    }
}
