<?php

namespace Flute\Core\Page;

use Flute\Core\Page\Parsers\CardParser;
use Flute\Core\Page\Parsers\DelimiterParser;
use Flute\Core\Page\Parsers\EmbedParser;
use Flute\Core\Page\Parsers\HeaderParser;
use Flute\Core\Page\Parsers\ImageParser;
use Flute\Core\Page\Parsers\ListParser;
use Flute\Core\Page\Parsers\ParagraphParser;
use Flute\Core\Page\Parsers\RawParser;
use Flute\Core\Page\Parsers\RowParser;
use Flute\Core\Page\Parsers\TableParser;
use Flute\Core\Page\Parsers\ToggleParser;
use Flute\Core\Page\Parsers\WidgetParser;
use Flute\Core\Page\Tunes\AlignmentTune;
use Flute\Core\Page\Tunes\ColTune;

/**
 * Class PageEditor
 *
 * The PageEditor class is responsible for parsing page data into its
 * corresponding components and applying specified tunes.
 *
 * @package Flute\Core\Page
 */
class PageEditorParser
{
    /**
     * Array to store parser classes by type
     */
    protected array $parsers = [
        'paragraph' => ParagraphParser::class,
        'card' => CardParser::class,
        'delimiter' => DelimiterParser::class,
        'embed' => EmbedParser::class,
        'list' => ListParser::class,
        'header' => HeaderParser::class,
        'row' => RowParser::class,
        'table' => TableParser::class,
        'widget' => WidgetParser::class,
        'image' => ImageParser::class,
        'raw' => RawParser::class
    ];

    /**
     * Array to store tune classes by type
     */
    protected array $tunes = [
        'alignment' => AlignmentTune::class,
        'col' => ColTune::class
    ];

    /**
     * Add a new tune class
     *
     * @param string $name
     * @param string $tune
     */
    public function addTune(string $name, string $tune): void
    {
        $this->tunes[$name] = $tune;
    }

    /**
     * Add a new parser class
     *
     * @param string $name
     * @param string $parser
     */
    public function addParser(string $name, string $parser)
    {
        $this->parsers[$name] = $parser;
    }

    /**
     * Get a parser class by its name
     *
     * @param string $name
     * @return string|null
     */
    public function getParser(string $name): ?string
    {
        return $this->parsers[$name] ?? null;
    }

    /**
     * Get a tune class by its name
     *
     * @param string $name
     * @return string|null
     */
    public function getTune(string $name): ?string
    {
        return $this->tunes[$name] ?? null;
    }

    /**
     * Parse the input data array and return the parsed string
     *
     * @param array $data
     * @return string
     * @throws \RuntimeException
     */
    public function parse(array $data): string
    {
        $result = '';
        
        foreach ($data as $value) {

            $parser = $this->getParser($value['type']);

            if (!$parser) {
                throw new \RuntimeException("No parser found for type {$value['type']}");
            }

            $parsed = call_user_func_array([new $parser, 'parse'], [
                $value['data'],
                $value['id']
            ]);

            if (!empty( $parsed ) && !empty( $value['tunes'] ) ) {
                $this->applyTunes($value['tunes'], $parsed);
            }

            $result .= $parsed;
        }

        return $result;
    }

    /**
     * Apply the specified tunes to the input content
     *
     * @param array $tunes
     * @param string $content
     * @throws \RuntimeException
     */
    protected function applyTunes(array $tunes, string &$content): void
    {
        foreach ($tunes as $key => $value) {
            $tune = $this->getTune($key);

            if (!$tune) {
                throw new \RuntimeException("No tune found for key {$key}");
            }

            $content = call_user_func([new $tune, 'parse'], $value, $content);
        }
    }
}
