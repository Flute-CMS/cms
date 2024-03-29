<?php 

namespace Flute\Core\Events;

use Symfony\Contracts\EventDispatcher\Event;

class AfterRenderEvent extends Event
{
    public const NAME = 'view.after_render';

    private string $content;

    public function __construct(&$content)
    {
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent(&$content)
    {
        $this->content = $content;
    }
}