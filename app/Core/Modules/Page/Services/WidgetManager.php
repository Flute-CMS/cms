<?php

namespace Flute\Core\Modules\Page\Services;

use Flute\Core\Modules\Page\Widgets\ActivePromoCodesWidget;
use Flute\Core\Modules\Page\Widgets\ContentWidget;
use Flute\Core\Modules\Page\Widgets\Contracts\WidgetInterface;
use Flute\Core\Modules\Page\Widgets\EditorWidget;
use Flute\Core\Modules\Page\Widgets\EmptyWidget;
use Flute\Core\Modules\Page\Widgets\RecentPaymentsWidget;
use Flute\Core\Modules\Page\Widgets\TopDonorsWidget;
use Flute\Core\Modules\Page\Widgets\UserMiniProfileWidget;
use Flute\Core\Modules\Page\Widgets\UsersNewWidget;
use Flute\Core\Modules\Page\Widgets\UsersOnlineWidget;
use Flute\Core\Modules\Page\Widgets\UsersTodayWidget;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;

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
    public function registerWidget(string $name, string $class): void
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException("Class {$class} does not exist.");
        }

        if (!in_array(WidgetInterface::class, class_implements($class ?? []))) {
            throw new InvalidArgumentException("Class {$class} must implement WidgetInterface.");
        }

        if (isset($this->widgets[$name])) {
            if ($this->widgets[$name] !== $class) {
                throw new InvalidArgumentException("Widget already exists - {$name}");
            }
        }

        $this->widgets[$name] = $class;

        $base = preg_replace('/Widget$/', '', $name);
        if ($base && !isset($this->widgets[$base])) {
            $this->widgets[$base] = $class;
        }
        $kebab = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $base));
        if ($kebab && !isset($this->widgets[$kebab])) {
            $this->widgets[$kebab] = $class;
        }
        $kebabUcFirst = ucfirst($kebab);
        if ($kebabUcFirst && !isset($this->widgets[$kebabUcFirst])) {
            $this->widgets[$kebabUcFirst] = $class;
        }
    }

    /**
     * Returns all registered widgets as name->instance.
     */
    public function getWidgets(): array
    {
        $instances = [];
        $seenClasses = [];

        foreach ($this->widgets as $name => $class) {
            if (isset($seenClasses[$class])) {
                continue;
            }

            $seenClasses[$class] = true;

            $instance = $this->container->get($class);

            if (method_exists($instance, 'isVisible') && !$instance->isVisible()) {
                continue;
            }

            $instances[$name] = $instance;
        }

        return $instances;
    }

    /**
     * Returns a single widget instance by name.
     */
    public function getWidget(string $name): WidgetInterface
    {
        if (!isset($this->widgets[$name])) {
            throw new InvalidArgumentException("Widget {$name} is not registered in the system.");
        }

        return $this->container->get($this->widgets[$name]);
    }

    /**
     * Registers default widgets.
     */
    public function registerDefaultWidgets(): void
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
        $this->registerWidget('Content', ContentWidget::class);
    }

    /**
     * Returns widgets grouped by their categories.
     */
    public function getWidgetsByCategory(): array
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
