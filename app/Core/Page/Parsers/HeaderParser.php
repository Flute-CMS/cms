<?php

namespace Flute\Core\Page\Parsers;

use Flute\Core\Contracts\ParserInterface;
use Nette\Utils\Html;

class HeaderParser implements ParserInterface
{
    public function parse(array $array, string $id)
    {
        $html = Html::el('h'.(int) $array['level']);
        $html->setHtml($array['text']);

        return $html->toHtml();
    }
}