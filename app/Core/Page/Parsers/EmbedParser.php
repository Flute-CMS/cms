<?php

namespace Flute\Core\Page\Parsers;

use Flute\Core\Contracts\ParserInterface;
use Nette\Utils\Html;

class EmbedParser implements ParserInterface
{
    public function parse(array $array, string $id)
    {
        $embedContainer = Html::el('div');
        $embedContainer->addClass('embed-container');
        
        $embed = Html::el('iframe');
        $embed->setAttribute('src', $array['embed']);
        $embed->setAttribute('width', $array['width']);
        $embed->setAttribute('height', $array['height']);
        $embed->setAttribute('frameborder', '0');
        $embed->setAttribute('allowfullscreen', 'true');
        $embed->setAttribute('allow', 'autoplay; encrypted-media; gyroscope; picture-in-picture');

        $embedCaption = Html::el('div');
        $embedCaption->addClass('embed-caption');
        $embedCaption->setText($array['caption']);
        
        $embedContainer->addHtml($embed);
        $embedContainer->addHtml($embedCaption);

        return $embedContainer->toHtml();
    }
}