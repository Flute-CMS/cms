<?php

namespace Flute\Core\Page\Parsers;

use Flute\Core\Contracts\ParserInterface;
use Flute\Core\Page\PageEditorParser;
use Nette\Utils\Html;

class RowParser implements ParserInterface
{
    public function parse(array $array, string $id)
    {
        $blocks = $array['blocks'];

        if( !$blocks )
            return;

        $parse = app(PageEditorParser::class)->parse($blocks);

        $rowDiv = Html::el('div');
        $rowDiv->addClass('row')->addClass('gx-4')->addClass('gy-4');
        $rowDiv->addHtml($parse);

        return $rowDiv->toHtml();
    }
}