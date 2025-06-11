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
        // Resolve translation placeholders before converting to HTML.
        // Supports two syntaxes: {{ __("lang.key") }} and [[trans:lang.key]]
        $markdown = preg_replace_callback('/\{\{\s*__\([\'\"]([^\'\"]+)[\'\"]\)\s*\}\}/', function ($matches) {
            return __($matches[1]);
        }, $markdown);

        $markdown = preg_replace_callback('/\[\[trans:([a-zA-Z0-9_.-]+)]]/', function ($matches) {
            return __($matches[1]);
        }, $markdown);

        $this->converter->setSafeMode($safe);
        $this->converter->setMarkupEscaped($setMarkupEscaped);
        
        return $this->converter->text($markdown);
    }
}
