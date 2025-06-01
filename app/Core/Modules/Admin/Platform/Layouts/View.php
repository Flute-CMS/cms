<?php

namespace Flute\Admin\Platform\Layouts;

use Flute\Admin\Platform\Layout;
use Flute\Admin\Platform\Repository;

/**
 * Class View.
 */
abstract class View extends Layout
{
    /**
     * @var array
     */
    private $data;

    /**
     * View constructor.
     *
     * @param \Illuminate\Contracts\Support\Arrayable|array $data
     */
    public function __construct(string $template, $data = [])
    {
        $this->template = $template;
        $this->data = $data;
    }

    /**
     * @return mixed
     */
    public function build(Repository $repository)
    {
        $this->query = $repository;

        if (!$this->isVisible()) {
            return;
        }

        $data = array_merge($this->data, $repository->toArray());

        return view($this->template, $data);
    }
}
