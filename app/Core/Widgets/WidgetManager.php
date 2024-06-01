<?php

namespace Flute\Core\Widgets;

use Cycle\ORM\Select\Repository;
use DI\DependencyException;
use DI\NotFoundException;
use Flute\Core\App;
use Flute\Core\Contracts\WidgetInterface;
use Flute\Core\Database\Entities\Widget;
use Flute\Core\Http\Controllers\WidgetController;
use Flute\Core\Router\RouteDispatcher;
use Flute\Core\Router\RouteGroup;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use RuntimeException;
use Throwable;

/**
 * Class WidgetManager
 * 
 * Manage widgets operations like register, load etc.
 */
class WidgetManager
{
    protected array $widgets = [];
    protected RouteDispatcher $routeDispatcher;
    protected ?Repository $widgetRepository = null;
    protected bool $performance;
    protected const CACHE_TIME = 24 * 60 * 60;

    /**
     * WidgetManager constructor.
     *
     * Load all widgets from the database upon instantiation.
     */
    public function __construct(RouteDispatcher $routeDispatcher)
    {
        $this->routeDispatcher = $routeDispatcher;

        $this->performance = (bool) (app('app.mode') === App::PERFORMANCE_MODE);

        if (is_installed()) {
            $this->loadAllWidgetsFromDB();
            $this->loadAllRoutes();
        }
    }

    protected function getRepository(): Repository
    {
        if ($this->widgetRepository !== null)
            return $this->widgetRepository;

        return $this->widgetRepository = rep(Widget::class);
    }

    protected function loadAllRoutes(): void
    {
        $this->routeDispatcher->group(function (RouteGroup $routeGroup) {
            $routeGroup->post('show', [WidgetController::class, 'showWidget']);
            $routeGroup->get('all', [WidgetController::class, 'getAllWidgets']);
        }, 'widget/');
    }

    /**
     * Registers a new widget and saves it in the database.
     *
     * @param WidgetInterface $widget
     * 
     * @throws RuntimeException|Throwable if the widget is already registered.
     */
    public function register(WidgetInterface $widget)
    {
        $loaderClassName = $this->getWidgetLoaderClassName($widget);

        if (isset($this->widgets[$loaderClassName])) {
            return; // Ignore if widget is already registered.
        }

        // Cache widget in local memory.
        $this->widgets[$loaderClassName] = $widget;

        // Save widget to the database.
        $newWidget = new Widget;
        $newWidget->name = $widget->getName();
        $newWidget->image = $widget->getImage();
        $newWidget->loader = $loaderClassName;
        $newWidget->lazyload = $widget->isLazyLoad();

        foreach ($widget->getDefaultSettings() as $key => $setting) {
            $newWidget->addSetting($setting);
        }

        transaction($newWidget)->run();
    }

    /**
     * Loads and renders a widget based on its loader class name.
     * 
     * @param string $loaderClassName
     * @param array $settingValues
     * @param ?string $asyncId
     * 
     * @return string Rendered widget.
     * 
     * @throws RuntimeException if the widget cannot be found.
     */
    public function loadAndRenderWidget(string $loaderClassName, array $settingValues, string $asyncId = null): string
    {
        /** @var WidgetInterface $widget */
        $widget = $this->getWidgetLoader($loaderClassName);

        if ($widget->isLazyLoad()) {
            $this->addAsyncWidget($loaderClassName, $widget, $settingValues, $asyncId);

            return config('app.widget_placeholders') ? $widget->placeholder($settingValues) : '';
        } else {
            foreach ($widget->getAssets() as $asset) {
                $linkAsset = template()->getTemplateAssets()->rAssetFunction($asset);

                if ($linkAsset) {
                    if (strpos($linkAsset, '<script') !== false) {
                        template()->section('footer', $linkAsset);
                    } else if (strpos($linkAsset, '<link') !== false) {
                        template()->section('header', $linkAsset);
                    }
                }
            }
        }

        return $widget->render($settingValues);
    }

    /**
     * Get widget by its loader class name.
     * 
     * @param string $loaderClassName
     * 
     * @return WidgetInterface
     */
    public function get(string $loaderClassName): WidgetInterface
    {
        if (!isset($this->widgets[$loaderClassName])) {
            throw new RuntimeException("Widget '{$loaderClassName}' not found");
        }

        return $this->widgets[$loaderClassName];
    }

    // Kostyl function.
    private function addAsyncWidget(string $loader, WidgetInterface $widget, $settings = false, ?string $asyncId = null): void
    {
        $params = [
            "loader" => $loader,
            "name" => $widget->getName(),
            "settings" => $settings ?? $widget->getSettings(),
            'assets' => []
        ];

        foreach ($widget->getAssets() as $asset) {
            $linkAsset = template()->getTemplateAssets()->rAssetFunction($asset);

            if ($linkAsset)
                $params['assets'][] = $linkAsset;
        }

        $encoded = $widget->encryptParams() ? sprintf(
            '"%s"',
            encrypt()->encrypt(
                $params
            )
        ) : Json::encode($params);

        $reload = $widget->getReloadTime() ?? 0;

        template()->section('footer', "<script>$(window).on('load', () => { addWidgetConfig($encoded, '$asyncId', $reload); });</script>");
    }

    /**
     * Retrieve a widget loader based on the provided loader class name.
     * 
     * @param string $loaderClassName
     * 
     * @return WidgetInterface
     * 
     * @throws RuntimeException
     */
    public function getWidgetLoader(string $loaderClassName): object
    {
        if (!isset($this->widgets[$loaderClassName])) {
            throw new RuntimeException("Widget loader '{$loaderClassName}' not found");
        }

        return $this->widgets[$loaderClassName];
    }

    /**
     * Retrieve the widget loader class name.
     * 
     * @param WidgetInterface $widget
     * @return string
     */
    private function getWidgetLoaderClassName(WidgetInterface $widget): string
    {
        return get_class($widget);
    }

    /**
     * Load all widgets from the database and register their loaders.
     */
    private function loadAllWidgetsFromDB(): void
    {
        // $widgets = $this->performance ? cache()->callback("flute.widget.all", function () {
        //     return $this->getAllWidgets();
        // }, self::CACHE_TIME) : $this->getAllWidgets();
        $widgets = $this->getAllWidgets();

        foreach ($widgets as $widget) {

            try {
                if (!class_exists($widget->loader))
                    throw new RuntimeException("Widget loader '{$widget->loader}' not callable");

                $this->registerWidgetLoader($widget->loader);

                $widgetLoader = $this->getWidgetLoader($widget->loader);

                $widgetLoader->setSettings(
                    $this->settingsToNormalView($widget->settings->toArray())
                );
                $widgetLoader->setLazyLoad((bool) $widget->lazyload);
                $widgetLoader->setImage($widget->image);

            } catch (RuntimeException $e) {
                logs()->error($e);
            }
        }
    }

    protected function getAllWidgets(): array
    {
        return $this->getRepository()->select()->load(['settings'])->fetchAll();
    }

    /**
     * @throws JsonException
     */
    private function settingsToNormalView(array $settings): array
    {
        $result = [];

        foreach ($settings as $key => $value) {
            $result[$key] = [
                "type" => $value->type,
                "id" => $value->id,
                "name" => $value->name,
                "value" => Json::decode($value->value),
                "description" => $value->description,
            ];
        }

        return $result;
    }

    /**
     * Register a widget loader.
     *
     * @param string $loaderClassName
     * @throws DependencyException
     */
    private function registerWidgetLoader(string $loaderClassName): void
    {
        try {
            $classInstance = app()->get($loaderClassName);
        } catch (NotFoundException $e) {
            throw new RuntimeException("Widget loader '{$loaderClassName}' not found");
        }

        $classInstance->init();

        $this->widgets[$loaderClassName] = $classInstance;
    }


    /**
     * Retrieve a widget from the database based on its name and loader.
     * 
     * @param string $name
     * @param string $loaderClassName
     * @return object
     */
    public function getWidgetFromDB(string $name, string $loaderClassName): object
    {
        return $this->getRepository()->findOne([
            'name' => $name,
            'loader' => $loaderClassName
        ]);
    }

    /**
     * Get all registered widgets.
     * 
     * @return array
     */
    public function getWidgets(): array
    {
        $resultArray = [];

        foreach ($this->widgets as $key => $widget) {
            $resultArray[] = [
                'name' => $widget->getName(),
                'loader' => $key,
                'settings' => $widget->getSettings(),
                'lazyload' => $widget->isLazyLoad(),
                'image' => $widget->getImage()
            ];
        }

        return $resultArray;
    }

    public function unregister(string $widgetLoader): void
    {
        $widget = $this->widgetRepository->findOne([
            'loader' => $widgetLoader
        ]);

        if ($widget) {
            try {
                if ($loader = $this->getWidgetLoader($widgetLoader)) {
                    $loader->unregister();
                }

                unset($this->widgets[$widgetLoader]);

                transaction($widget, 'delete')->run();
            } catch (\Exception $e) {
                //
            }
        }
    }

    /**
     * Check if a widget is already present in the database.
     * 
     * @param string $name
     * @param WidgetInterface $widget
     * @return bool
     */
    protected function isWidgetInDatabase(string $name, WidgetInterface $widget): bool
    {
        return !empty($this->getWidgetFromDB($name, $this->getWidgetLoaderClassName($widget)));
    }
}