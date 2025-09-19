<?php

namespace Flute\Core\Markdown;

use Parsedown;

class Parser
{
    protected Parsedown $converter;

    public function __construct()
    {
        $this->converter = new Parsedown();
        $this->converter->setBreaksEnabled(false);
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
            static fn ($m) => __($m[1]),
            $markdown
        );
        $markdown = preg_replace_callback(
            '/\[\[trans:([a-zA-Z0-9_.-]+)]]/',
            static fn ($m) => __($m[1]),
            $markdown
        );

        $markdown = preg_replace('/\n{3,}/', "\n[[MD_BREAK_2]]\n", $markdown);

        $this->converter->setSafeMode($safe)
            ->setMarkupEscaped($setMarkupEscaped)
            ->setBreaksEnabled(false);

        $html = $this->converter->text($markdown);
        $html = str_replace('[[MD_BREAK_2]]', '<br><br>', $html);

        return $html;
    }
}
