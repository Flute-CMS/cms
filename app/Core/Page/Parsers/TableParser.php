<?php

namespace Flute\Core\Page\Parsers;

use Flute\Core\Contracts\ParserInterface;
use Flute\Core\Table\TableBuilder;
use Nette\Utils\Html;

/**
 * Class TableParser
 *
 * The TableParser class is responsible for parsing table data into an HTML table using Nette Html.
 *
 * @package Flute\Core\Page\Parsers
 */
class TableParser implements ParserInterface
{
    public function parse(array $data, string $id)
    {
        $table = Html::el('table')->class('dataTable');

        $withHeadings = $data['withHeadings'] ?? false;
        $content = $data['content'] ?? [];

        foreach ($content as $index => $row) {
            $tableRow = Html::el('tr');
            
            foreach ($row as $cellData) {
                $cellType = ($index === 0 && $withHeadings) ? 'th' : 'td';
                $cell = Html::el($cellType)->setHtml($cellData);
                $tableRow->addHtml($cell);
            }

            $table->addHtml($tableRow);
        }

        return $table->toHtml();
    }
}