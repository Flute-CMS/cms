<?php

namespace Flute\Core\Modules\Translation\Events;

class LangChangedEvent
{
    public const NAME = 'flute.lang_changed';

    private string $newLang;

    public function __construct(string $newLang)
    {
        $this->newLang = $newLang;
    }

    public function getNewLang(): string
    {
        return $this->newLang;
    }
}
