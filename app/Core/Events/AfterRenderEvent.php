<?php

namespace Flute\Core\Events;

use Illuminate\View\View;
use Symfony\Contracts\EventDispatcher\Event;

class AfterRenderEvent extends Event
{
    public const NAME = 'view.after_render';

    private View $view;

    public function __construct(&$view)
    {
        $this->view = $view;
    }

    public function getView()
    {
        return $this->view;
    }

    public function setView(&$view)
    {
        $this->view = $view;
    }
}
