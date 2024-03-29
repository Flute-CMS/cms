<?php

namespace Flute\Core\Page\Tunes;

use Flute\Core\Contracts\TuneInterface;
use Nette\Utils\Html;

class AlignmentTune implements TuneInterface
{
    public function parse( $alignment, string $content )
    {
        $div = Html::el('div');
        $div->addAttributes([
            'style' => 'text-align: '. $alignment['alignment'],
        ]);
        $div->addHtml( $content );
        
        return $div->toHtml();
    }
}