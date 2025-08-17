<?php

namespace Flute\Core\Template\Events;

use Flute\Core\Template\Template;
use Symfony\Contracts\EventDispatcher\Event;

class TemplateInitialized extends Event
{
    public const NAME = 'template.initialized';

    protected Template $template;

    public function __construct(Template $template)
    {
        $this->template = $template;
    }

    public function getTemplate(): Template
    {
        return $this->template;
    }
}
