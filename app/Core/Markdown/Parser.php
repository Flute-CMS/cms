<?php

namespace Flute\Core\Markdown;

use Parsedown;

class Parser
{
    protected Parsedown $converter;

    public function __construct()
    {
        $this->converter = new Parsedown();
    }

    /**
     * Parse markdown to html
     * 
     * @param string $markdown Markdown text to parse
     * @param bool $safe Whether to enable safe mode
     * @param bool $setMarkupEscaped Whether to set the markup escaped
     * 
     * @return string Parsed html
     */
    public function parse(string $markdown, bool $safe = true, bool $setMarkupEscaped = true) : string
    {
        $this->converter->setSafeMode($safe);
        $this->converter->setMarkupEscaped($setMarkupEscaped);
        
        return $this->converter->text($markdown);
    }
}
