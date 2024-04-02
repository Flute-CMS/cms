<?php

namespace Flute\Core\Page\Parsers;

use Flute\Core\Contracts\ParserInterface;
use Nette\Utils\Html;

class ParagraphParser implements ParserInterface
{
    public function parse(array $array, string $id)
    {
        $text = Html::el('p');
        $text->addHtml($array['text']);

        return $text->toHtml();
    }
}