<?php 

namespace Flute\Core\Events;

use Symfony\Contracts\EventDispatcher\Event;

class BeforeRenderEvent extends Event
{
    public const NAME = 'view.before_render';

    private $view;
    private $data;

    public function __construct($view, $data)
    {
        $this->view = $view;
        $this->data = $data;
    }

    public function getView()
    {
        return $this->view;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($key, $value)
    {
        $this->data[$key] = $value;
    }
}