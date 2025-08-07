<?php

namespace Flute\Admin\Platform;

use Flute\Admin\Platform\Contracts\Actionable;
use Flute\Admin\Platform\Support\Color;

class Action extends Field implements Actionable
{
    /**
     * Override the form view.
     *
     * @var string
     */
    protected $typeForm = 'admin::partials.fields.clear';

    /**
     * Attributes available for a particular tag.
     *
     * @var array
     */
    protected $inlineAttributes = [
        'type',
        'autofocus',
        'disabled',
        'tabindex',
        'target',
    ];

    /**
     * A set of attributes for the assignment
     * of which will automatically translate them.
     *
     * @var array
     */
    protected $translations = [
        'name',
    ];

    public function name(?string $name = null): self
    {
        return $this->set('name', $name ?? '');
    }

    /**
     * @return static
     */
    public function type(Color $visual): self
    {
        return $this->set('type', $visual->name());
    }

    /**
     * @throws \Throwable
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|mixed
     */
    public function build(?Repository $repository = null)
    {
        return $this->render();
    }

    /**
     * @return string
     */
    protected function getId(): ?string
    {
        return $this->get('id');
    }
}
