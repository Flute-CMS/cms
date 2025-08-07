<?php

namespace Flute\Core\Markdown;

use Parsedown;

class Parser
{
    protected Parsedown $converter;

    public function __construct()
    {
        $this->converter = new Parsedown();
        $this->converter->setBreaksEnabled(true);
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
    public function parse(string $markdown, bool $safe = true, bool $setMarkupEscaped = true): string
    {
        $markdown = preg_replace_callback(
            '/\{\{\s*__\([\'\"]([^\'\"]+)[\'\"]\)\s*\}\}/',
            fn ($m) => __($m[1]),
            $markdown
        );
        $markdown = preg_replace_callback(
            '/\[\[trans:([a-zA-Z0-9_.-]+)]]/',
            fn ($m) => __($m[1]),
            $markdown
        );

        /*
         * Add empty line:
         * convert sequence of two (or more) \n to \n<br>\n – Parsedown will see a normal paragraph,
         * and we will get a visual empty line between them.
         */
        $markdown = preg_replace('/\n{2,}/', "\n<br>\n", $markdown);

        $this->converter->setSafeMode($safe)
            ->setMarkupEscaped($setMarkupEscaped)
            ->setBreaksEnabled(true);

        return $this->converter->text($markdown);
    }
}
