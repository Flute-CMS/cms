<?php

namespace Flute\Core\Page\Parsers;

use Flute\Core\Contracts\ParserInterface;
use Flute\Core\Page\PageEditorParser;
use Nette\Utils\Html;

class RawParser implements ParserInterface
{
    public function parse(array $array, string $id)
    {
        $el = Html::fromHtml(template()->getBlade()->runString($array['html']));

        return $el->toHtml();
    }
}