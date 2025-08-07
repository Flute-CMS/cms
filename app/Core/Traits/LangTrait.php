<?php

namespace Flute\Core\Traits;

use DI\DependencyException;
use DI\NotFoundException;
use Flute\Core\Modules\Translation\Events\LangChangedEvent;
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
    public function setLang(string $lang): self
    {
        $this->lang = $lang;

        $this->bind('lang', $lang);

        $this->get(EventDispatcher::class)
            ->dispatch(new LangChangedEvent($lang), LangChangedEvent::NAME);

        return $this;
    }

    public function getLang(): ?string
    {
        return $this->lang;
    }
}
