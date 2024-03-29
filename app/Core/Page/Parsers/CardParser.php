<?php

namespace Flute\Core\Page\Parsers;

use Flute\Core\Contracts\ParserInterface;
use Flute\Core\Page\PageEditorParser;
use Nette\Utils\Html;

class CardParser implements ParserInterface
{
    public function parse(array $array, string $id)
    {
        $blocks = $array;

        if( !$blocks )
            return;

        $parse = app(PageEditorParser::class)->parse($blocks);

        $rowDiv = Html::el('div');
        $rowDiv->addClass('card');
        $rowDiv->addHtml($parse);

        return $rowDiv->toHtml();
    }
}