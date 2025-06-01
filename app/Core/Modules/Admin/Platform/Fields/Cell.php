<?php

namespace Flute\Admin\Platform\Fields;

use Closure;
use Cycle\ORM\EntityProxyInterface;
use Flute\Admin\Platform\Repository;
use Flute\Admin\Platform\Support\Blade;
use Flute\Admin\Platform\Traits\IsVisible;
use Flute\Core\Traits\MacroableTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

abstract class Cell
{
    use IsVisible, MacroableTrait;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var Closure|null
     */
    protected $render;

    /**
     * @var string
     */
    protected $column;

    /**
     * @var string
     */
    protected $popover;

    /**
     * Cell constructor.
     */
    public function __construct(string $name)
    {
        $this->name = $name;
        $this->column = $name;
    }

    /**
     * @return static
     */
    public static function make(string $name = '', ?string $title = null): static
    {
        $td = new static($name);
        $td->column = $name;
        $td->title = $title ?? Str::title($name);

        return $td;
    }

    public function render(Closure $closure): static
    {
        $this->render = $closure;

        return $this;
    }

    public function popover(string $text): static
    {
        $this->popover = $text;

        return $this;
    }

    /**
     * @throws \ReflectionException
     *
     * @return string
     */
    protected function getNameParameterExpected(string $component, array $params = []): ?string
    {
        $class = new \ReflectionClass($component);
        $parameters = optional($class->getConstructor())->getParameters() ?? [];

        $paramsKeys = Arr::isAssoc($params) ? array_keys($params) : array_values($params);

        return collect($parameters)
            ->filter(fn(\ReflectionParameter $parameter) => !$parameter->isOptional())
            ->whenEmpty(fn() => collect($parameters))
            ->map(fn(\ReflectionParameter $parameter) => $parameter->getName())
            ->diff($paramsKeys)
            ->last();
    }

    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \ReflectionException
     */
    protected function renderComponent(string $component, $value, array $params = []): ?string
    {
        [$class, $view] = Blade::componentInfo($component);

        if ($view === null) {
            // for class based components, try to detect argument name
            $nameArgument = $this->getNameParameterExpected($class, $params);
            if ($nameArgument !== null) {
                $params[$nameArgument] = $value;
            }
        }

        $params = array_map(fn($item) => value($item, $value), $params);

        return Blade::renderComponent($component, $params);
    }

    /**
     * Pass only the cell value to the component
     *
     * @throws \ReflectionException
     *
     * @return $this
     */
    public function asComponent(string $component, array $params = []): static
    {
        return $this->render(function ($value) use ($component, $params) {
            if ($value instanceof Repository) {
                $content = $value->getContent($this->name);
            } elseif ($value instanceof EntityProxyInterface) {
                $content = $value->{$this->name};
            } else {
                throw new \InvalidArgumentException("Unsupported source type for asComponent.");
            }

            $params['_row'] = $value;

            return $this->renderComponent($component, $content, $params);
        });
    }

    /**
     * @param Repository|\Cycle\ORM\EntityProxyInterface $source
     *
     * @return mixed
     */
    protected function handler($source, ?object $loop = null)
    {
        $callback = $this->render;

        return is_null($callback) ? $source : $callback($source, $loop);
    }

    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }
}
