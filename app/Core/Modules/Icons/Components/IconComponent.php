<?php

namespace Flute\Core\Modules\Icons\Components;

use Flute\Core\Modules\Icons\Services\IconFinder;
use Illuminate\View\Component;
use Illuminate\View\View;

class IconComponent extends Component
{
    /**
     * @var string|null
     */
    public $class;

    /**
     * @var string
     */
    public $width;

    /**
     * @var string
     */
    public $height;

    /**
     * @var string
     */
    public $role;

    /**
     * @var string
     */
    public $fill;

    /**
     * @var string
     */
    public $id;

    /**
     * Icon tag
     *
     * @var string
     */
    public $path;

    /**
     */
    protected IconFinder $finder;

    /**
     * Create a new component instance.
     */
    public function __construct(
        string $path,
        ?string $id = null,
        ?string $class = null,
        ?string $width = null,
        ?string $height = null,
        string $role = 'img',
        string $fill = 'currentColor',
    ) {
        $finder = app(IconFinder::class);

        $this->path = $path;
        $this->id = $id;
        $this->class = $class;
        $this->width = $width ?? $finder->getDefaultWidth();
        $this->height = $height ?? $finder->getDefaultHeight();
        $this->role = $role;
        $this->fill = $fill;
        $this->finder = $finder;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): callable
    {
        return fn (array $data = []) => render('flute-icons::icon', [
            'html' => $this->finder->loadFile($this->path),
            'data' => collect($this->extractPublicProperties())->merge($data['attributes'] ?? [])->filter(static fn ($value) => is_string($value)),
        ])->render();
    }

    /**
     *
     */
    public static function make(...$params): View
    {
        return app()->make(static::class, $params)->render()();
    }
}
