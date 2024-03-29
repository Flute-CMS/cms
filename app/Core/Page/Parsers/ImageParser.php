<?php

namespace Flute\Core\Page\Parsers;

use Flute\Core\Contracts\ParserInterface;
use Nette\Utils\Html;

class ImageParser implements ParserInterface
{
    public function parse(array $attributes, string $id)
    {
        // Create a figure element
        $figure = Html::el('figure');

        // Create an image element
        $img = Html::el('img')
            ->src($attributes['file']['url'])
            ->alt(isset($attributes['caption']) ? $attributes['caption'] : '');

        // Append the image to the figure
        $figure->addHtml($img);

        // Check if caption is provided and add it
        if (!empty($attributes['caption'])) {
            $caption = Html::el('figcaption')->setText($attributes['caption']);
            $figure->addHtml($caption);
        }

        $figure->class = 'editor-image';

        // Check for additional styling attributes
        if ($attributes['withBorder']) {
            $figure->class('editor-image border-class'); // Replace 'border-class' with your actual CSS class for borders
        }
        if ($attributes['stretched']) {
            $figure->class($figure->class ? $figure->class . ' stretched-class' : 'stretched-class'); // Add 'stretched-class'
        }
        if ($attributes['withBackground']) {
            $figure->class($figure->class ? $figure->class . ' background-class' : 'background-class'); // Add 'background-class'
        }

        // Return the HTML string
        return $figure->toString();
    }
}
