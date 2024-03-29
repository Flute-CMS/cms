<?php

namespace Flute\Core\Page\Parsers;

use Flute\Core\Contracts\ParserInterface;
use Nette\Utils\Html;

class DelimiterParser implements ParserInterface
{
    public function parse(array $array, string $id)
    {
        $div = Html::el('div');
        $div->class = 'flute-delimiter';
        $div->id = $id;

        return $div->toHtml();
    }
}