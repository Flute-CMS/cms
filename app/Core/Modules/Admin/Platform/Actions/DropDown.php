<?php

namespace Flute\Admin\Platform\Actions;

use Flute\Admin\Platform\Action;
use Flute\Admin\Platform\Repository;
use Throwable;

/**
 * Class DropDown.
 *
 * @method DropDown name(string $name = null)
 * @method DropDown modal(string $modalName = null)
 * @method DropDown icon(string $icon = null)
 * @method DropDown class(string $classes = null)
 */
class DropDown extends Action
{
    /**
     * @var string
     */
    protected $view = 'admin::actions.dropdown';

    /**
     * Default attributes value.
     *
     * @var array
     */
    protected $attributes = [
        'class' => 'btn btn-outline-primary',
        'source' => null,
        'icon' => null,
        'list' => [],
    ];

    /**
     * @param \Flute\Admin\Platform\Contracts\Actionable[] $list
     */
    public function list(array $list): self
    {
        return $this->set('list', $list);
    }

    /**
     * @throws Throwable
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|mixed
     */
    public function build(?Repository $repository = null)
    {
        $this->set('source', $repository);

        return $this->render();
    }
}
