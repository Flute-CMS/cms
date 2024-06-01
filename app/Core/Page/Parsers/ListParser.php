<?php

namespace Flute\Core\Page\Parsers;

use Flute\Core\Contracts\ParserInterface;
use Nette\Utils\Html;

class ListParser implements ParserInterface
{
    public function parse(array $array, string $id)
    {
        $style = $array['style'] ?? 'unordered';
        $listType = $style === 'ordered' ? 'ol' : 'ul';

        return $this->generateList($array['items'] ?? [], $listType)->toHtml();
    }

    /**
     * Recursively generate an HTML list from the provided items
     *
     * @param array $items
     * @param string $listType
     * @return Html
     */
    protected static function generateList(array $items, string $listType): Html
    {
        $list = Html::el($listType);

        foreach ($items as $itemData) {
            $item = Html::el('li');

            if (!empty($itemData['checked'])) {
                $checkbox = Html::el('div')->class('editor-checkbox');
                $item->addHtml($checkbox);
            }

            $item->addHtml($itemData['content']);
            $list->addHtml($item);

            // Generate nested list
            if (!empty($itemData['items'])) {
                $item->addHtml(self::generateList($itemData['items'], $listType));
            }
        }

        return $list;
    }
}