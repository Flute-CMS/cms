<?php

namespace Flute\Core\Traits;

use DI\DependencyException;
use DI\NotFoundException;
use Symfony\Component\EventDispatcher\EventDispatcher;

trait LangTrait
{
    /**
     * @var ?string
     */
    public ?string $lang = null;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function setLang(string $lang) : self
    {
        $this->lang = $lang;

        $this->bind('lang', $lang);
        
        // dump( debug_backtrace() );

        $this->get(EventDispatcher::class)
            ->dispatch(new \Flute\Core\Events\LangChangedEvent($lang), \Flute\Core\Events\LangChangedEvent::NAME);

        return $this;
    }

    public function getLang() : ?string
    {
        return $this->lang;
    }
}