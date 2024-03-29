<?php

namespace Flute\Core\Page\Tunes;

use Flute\Core\Contracts\TuneInterface;
use Nette\Utils\Html;

class ColTune implements TuneInterface
{
    public function parse( $value, string $content )
    {
        $div = Html::el('div');
        $div->addClass( 'col-md-'. $value );
        $div->addHtml( $content );
        
        return $div->toHtml();
    }
}