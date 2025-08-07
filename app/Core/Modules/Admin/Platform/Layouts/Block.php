<?php

namespace Flute\Admin\Platform\Layouts;

use Flute\Admin\Platform\Commander;
use Flute\Admin\Platform\Layout;
use Flute\Admin\Platform\Repository;
use Illuminate\Support\Arr;

/**
 * Class Block.
 */
abstract class Block extends Layout
{
    use Commander;

    /**
     * @var string
     */
    protected $template = 'admin::partials.layouts.block';

    /**
     * @var false[]
     */
    protected $variables = [
        'vertical' => true,
        'class' => '',
        'popover' => null,
        'morph' => null,
    ];

    /**
     * Button commands.
     *
     * @var array
     */
    protected $commandBar = [];

    /**
     * Layout constructor.
     *
     * @param Layout[] $layouts
     */
    public function __construct(array $layouts = [])
    {
        $this->layouts = $layouts;
    }

    protected function commandBar(): array
    {
        return $this->commandBar;
    }

    /**
     * @return mixed
     */
    public function build(Repository $repository)
    {
        $this->variables['commandBar'] = $this->buildCommandBar($repository);

        return $this->buildAsDeep($repository);
    }

    /**
     * Used to create the title of a group of form elements.
     */
    public function title(string $title): self
    {
        $this->variables['title'] = $title;

        return $this;
    }

    /**
     * Used to create the description of a group of form elements.
     *
     * @param string|\Illuminate\View\View description
     */
    public function description($description): self
    {
        $this->variables['description'] = $description;

        return $this;
    }

    /**
     * Used to define block orientation.
     *
     * @param bool $vertical
     */
    public function vertical($vertical = true): self
    {
        $this->variables['vertical'] = $vertical;

        return $this;
    }

    public function addClass(string $class): self
    {
        $this->variables['class'] .= ' ' . $class;

        return $this;
    }

    public function popover(string $popover): self
    {
        $this->variables['popover'] = $popover;

        return $this;
    }

    public function commands($commands): self
    {
        $this->commandBar = Arr::wrap($commands);

        return $this;
    }

    public function morph(bool $morph = false): self
    {
        $this->variables['morph'] = $morph;

        return $this;
    }
}
