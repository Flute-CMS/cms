<?php

namespace Flute\Admin\Platform\Layouts;

use Flute\Admin\Platform\Builder;
use Flute\Admin\Platform\Layout;
use Flute\Admin\Platform\Repository;

/**
 * Class Rows.
 */
abstract class Rows extends Layout
{
    /**
     * @var string
     */
    protected $template = 'admin::partials.layouts.row';

    /**
     * Used to create the title of a group of form elements.
     *
     * @var string|null
     */
    protected $title;

    /**
     * Used to create the description of a group of form elements.
     *
     * @var string|null
     */
    protected $description;

    /**
     * Used to create the popover of a group of form elements.
     *
     * @var string|null
     */
    protected $popover;

    /**
     * Used to create the class of a group of form elements.
     *
     * @var string|null
     */
    protected $class;

    /**
     * @var Repository
     */
    protected $query;

    public function build(Repository $repository)
    {
        $this->query = $repository;

        if (!$this->isVisible()) {
            return;
        }

        $form = new Builder($this->fields(), $repository);

        return view($this->template, [
            'form' => $form->generateForm(),
            'title' => $this->title,
            'description' => $this->description,
            'popover' => $this->popover,
            'class' => $this->class,
        ]);
    }

    public function title(?string $title = null): self
    {
        $this->title = $title;

        return $this;
    }

    public function description(?string $description = null): self
    {
        $this->description = $description;

        return $this;
    }

    public function class(?string $class = null): self
    {
        $this->class = $class;

        return $this;
    }

    public function popover(?string $popover = null): self
    {
        $this->popover = $popover;

        return $this;
    }

    /**
     * @return \Flute\Admin\Platform\Field[]
     */
    abstract protected function fields(): iterable;
}
