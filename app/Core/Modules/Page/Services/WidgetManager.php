<?php

namespace Flute\Core\Modules\Page\Services;

use Flute\Core\Modules\Page\Widgets\ActivePromoCodesWidget;
use Flute\Core\Modules\Page\Widgets\Contracts\WidgetInterface;
use Flute\Core\Modules\Page\Widgets\EmptyWidget;
use Flute\Core\Modules\Page\Widgets\RecentPaymentsWidget;
use Flute\Core\Modules\Page\Widgets\TopDonorsWidget;
use Flute\Core\Modules\Page\Widgets\UserMiniProfileWidget;
use Flute\Core\Modules\Page\Widgets\UsersNewWidget;
use Flute\Core\Modules\Page\Widgets\UsersOnlineWidget;
use Flute\Core\Modules\Page\Widgets\UsersTodayWidget;
use Flute\Core\Modules\Page\Widgets\EditorWidget;
use Psr\Container\ContainerInterface;
use InvalidArgumentException;

class WidgetManager
{
    /**
     * @var array<string, string>
     */
    protected array $widgets = [];

    protected ContainerInterface $container;

    /**
     * Constructor method.
     *
     * @param ContainerInterface $container The DI container.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Registers a widget class by name.
     */
    public function registerWidget(string $name, string $class) : void
    {
        if (!is_subclass_of($class, WidgetInterface::class)) {
            throw new InvalidArgumentException("Class {$class} must implement WidgetInterface.");
        }
        if (isset($this->widgets[$name])) {
            throw new InvalidArgumentException("Widget already exists - {$name}");
        }
        $this->widgets[$name] = $class;
    }

    /**
     * Returns all registered widgets as name->instance.
     */
    public function getWidgets() : array
    {
        $instances = [];
        foreach ($this->widgets as $name => $class) {
            $instances[$name] = $this->container->get($class);
        }
        return $instances;
    }

    /**
     * Returns a single widget instance by name.
     */
    public function getWidget(string $name) : WidgetInterface
    {
        if (!isset($this->widgets[$name])) {
            throw new InvalidArgumentException("Widget {$name} is not registered in the system.");
        }
        return $this->container->get($this->widgets[$name]);
    }

    /**
     * Registers default widgets.
     */
    public function registerDefaultWidgets() : void
    {
        $this->registerWidget('UsersNew', UsersNewWidget::class);
        $this->registerWidget('UsersOnline', UsersOnlineWidget::class);
        $this->registerWidget('UsersToday', UsersTodayWidget::class);
        $this->registerWidget('RecentPayments', RecentPaymentsWidget::class);
        $this->registerWidget('TopDonors', TopDonorsWidget::class);
        $this->registerWidget('ActivePromoCodes', ActivePromoCodesWidget::class);
        $this->registerWidget('Empty', EmptyWidget::class);
        $this->registerWidget('UserMiniProfile', UserMiniProfileWidget::class);
        $this->registerWidget('Editor', EditorWidget::class);
    }

    /**
     * Returns widgets grouped by their categories.
     */
    public function getWidgetsByCategory() : array
    {
        $categories = [];
        foreach ($this->getWidgets() as $name => $widget) {
            $category = $widget->getCategory();
            if (!isset($categories[$category])) {
                $categories[$category] = [];
            }
            $categories[$category][$name] = $widget;
        }
        return $categories;
    }
}
