<?php

namespace Flute\Core\Markdown;

use DOMComment;
use DOMDocument;
use DOMElement;
use DOMNode;
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
    public function parseMarkdown(string $markdown, bool $safe = true, bool $setMarkupEscaped = true): string
    {
        $markdown = $this->replaceTranslations($markdown);

        $breakPlaceholder = "\x00MD_BREAK_" . bin2hex(random_bytes(4)) . "\x00";
        $markdown = preg_replace('/\n{3,}/', "\n" . $breakPlaceholder . "\n", $markdown);

        $this->converter
            ->setSafeMode($safe)
            ->setMarkupEscaped($setMarkupEscaped)
            ->setBreaksEnabled(false);

        $html = $this->converter->text($markdown);
        $html = str_replace($breakPlaceholder, '<br><br>', $html);

        if ($safe) {
            $html = $this->sanitizeHtml($html);
        }

        return $html;
    }

    /**
     * Detect if content is HTML (from TipTap) rather than Markdown.
     */
    public function isHtml(string $content): bool
    {
        return (bool) preg_match(
            '/<(?:p|div|h[1-6]|ul|ol|li|table|br|img|a|strong|em|blockquote|pre|hr)\b/i',
            $content,
        );
    }

    /**
     * Render content — auto-detects HTML vs Markdown.
     * HTML content is sanitized and returned directly.
     * Markdown content is parsed via parse().
     */
    public function parse(string $content, bool $safe = true, bool $setMarkupEscaped = true): string
    {
        if ($this->isHtml($content)) {
            $content = $this->replaceTranslations($content);

            if ($safe) {
                $content = $this->sanitizeHtml($content);
            }

            return $content;
        }

        return $this->parseMarkdown($content, $safe, $setMarkupEscaped);
    }

    /**
     * Replace translation placeholders in content.
     */
    protected function replaceTranslations(string $content): string
    {
        // Note: no htmlspecialchars here — downstream sanitizers handle escaping.
        // HTML path: DOMDocument sanitizer strips dangerous tags.
        // Markdown path: Parsedown safe mode escapes HTML + DOMDocument sanitizer as second layer.
        // Applying htmlspecialchars here would cause double-encoding (&amp;lt; instead of &lt;).
        $content = preg_replace_callback(
            '/\{\{\s*__\([\'\"]([^\'\"]+)[\'\"]\)\s*\}\}/',
            static fn($m) => __($m[1]),
            $content,
        );

        return preg_replace_callback('/\[\[trans:([a-zA-Z0-9_.-]+)]]/', static fn($m) => __($m[1]), $content);
    }

    /**
     * Sanitize HTML content using DOM-based approach instead of regex.
     * Strips dangerous tags, attributes, and URI schemes.
     */
    protected function sanitizeHtml(string $html): string
    {
        $allowedTags = [
            'p',
            'br',
            'strong',
            'em',
            'u',
            's',
            'a',
            'img',
            'h1',
            'h2',
            'h3',
            'h4',
            'h5',
            'h6',
            'ul',
            'ol',
            'li',
            'blockquote',
            'pre',
            'code',
            'table',
            'thead',
            'tbody',
            'tr',
            'th',
            'td',
            'hr',
            'div',
            'span',
            'sub',
            'sup',
            'mark',
        ];

        $allowedAttributes = [
            'a' => ['href', 'title', 'target', 'rel', 'class'],
            'img' => ['src', 'alt', 'title', 'width', 'height', 'class'],
            'td' => ['colspan', 'rowspan', 'class'],
            'th' => ['colspan', 'rowspan', 'class'],
            'div' => ['class'],
            'span' => ['class'],
            'pre' => ['class'],
            'code' => ['class'],
            'blockquote' => ['class'],
            'p' => ['class'],
            'ul' => ['class'],
            'ol' => ['class'],
            'li' => ['class'],
            'h1' => ['class'],
            'h2' => ['class'],
            'h3' => ['class'],
            'h4' => ['class'],
            'h5' => ['class'],
            'h6' => ['class'],
            'table' => ['class'],
            'mark' => ['class'],
        ];

        $allowedSchemes = ['http', 'https', 'mailto'];

        $dom = new DOMDocument();
        $dom->encoding = 'UTF-8';
        $previousLibxmlState = libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="UTF-8"><div id="__sanitize_root__">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previousLibxmlState);

        foreach ($dom->childNodes as $item) {
            if ($item->nodeType === XML_PI_NODE) {
                $dom->removeChild($item);

                break;
            }
        }

        $this->sanitizeNode($dom->documentElement, $allowedTags, $allowedAttributes, $allowedSchemes);

        $root = $dom->getElementById('__sanitize_root__');
        if (!$root) {
            return '';
        }

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $dom->saveHTML($child);
        }

        return $output;
    }

    /**
     * Recursively sanitize a DOM node.
     */
    private function sanitizeNode(
        DOMNode $node,
        array $allowedTags,
        array $allowedAttributes,
        array $allowedSchemes,
    ): void {
        $nodesToRemove = [];

        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $tagName = strtolower($child->tagName);

                // Remove disallowed tags but keep their text content
                if (
                    !in_array($tagName, $allowedTags, true)
                    && $tagName !== 'div'
                    && $child->getAttribute('id') !== '__sanitize_root__'
                ) {
                    $nodesToRemove[] = $child;

                    continue;
                }

                // Remove disallowed attributes
                $attrsToRemove = [];
                foreach ($child->attributes as $attr) {
                    $attrName = strtolower($attr->name);

                    // Always remove event handlers (on*)
                    if (str_starts_with($attrName, 'on')) {
                        $attrsToRemove[] = $attr->name;

                        continue;
                    }

                    // Remove style attribute to prevent CSS-based attacks
                    if ($attrName === 'style') {
                        $attrsToRemove[] = $attr->name;

                        continue;
                    }

                    // Check against allowed attributes for this tag
                    $tagAllowed = $allowedAttributes[$tagName] ?? [];
                    if (!in_array($attrName, $tagAllowed, true)) {
                        $attrsToRemove[] = $attr->name;

                        continue;
                    }

                    // Sanitize class attribute: only allow safe characters (alphanumeric, hyphens, underscores, spaces)
                    if ($attrName === 'class') {
                        $cleanClass = preg_replace('/[^a-zA-Z0-9\s_-]/', '', $attr->value);
                        if ($cleanClass === '') {
                            $attrsToRemove[] = $attr->name;

                            continue;
                        }
                        $attr->value = $cleanClass;
                    }

                    // Validate numeric attributes (prevent layout-breaking values)
                    if (in_array($attrName, ['colspan', 'rowspan'], true)) {
                        $numVal = (int) $attr->value;
                        if ($numVal < 1 || $numVal > 20) {
                            $attrsToRemove[] = $attr->name;

                            continue;
                        }
                    }
                    if (in_array($attrName, ['width', 'height'], true)) {
                        // Allow only reasonable numeric values or percentages
                        if (!preg_match('/^\d{1,4}(%)?$/', trim($attr->value))) {
                            $attrsToRemove[] = $attr->name;

                            continue;
                        }
                    }

                    // Validate URI schemes for href and src
                    if (in_array($attrName, ['href', 'src'], true)) {
                        $value = trim($attr->value);

                        // Strip invisible control characters that can bypass scheme checks
                        $cleanValue = preg_replace('/[\x00-\x1F\x7F\xC2\xA0]/', '', $value);

                        // If the cleaned URL contains a colon, validate the scheme explicitly
                        if (str_contains($cleanValue, ':')) {
                            $isSafe = false;

                            // Allow only explicitly whitelisted schemes
                            foreach ($allowedSchemes as $scheme) {
                                if (str_starts_with(strtolower($cleanValue), $scheme . ':')) {
                                    $isSafe = true;

                                    break;
                                }
                            }

                            // Allow data:image URIs for img src (excluding svg+xml which can contain JS)
                            if (
                                !$isSafe
                                && $attrName === 'src'
                                && $tagName === 'img'
                                && preg_match('/^data:image\/(png|jpe?g|gif|webp);base64,/i', $cleanValue)
                            ) {
                                $isSafe = true;
                            }

                            if (!$isSafe) {
                                $attrsToRemove[] = $attr->name;

                                continue;
                            }
                        }

                        // Update the attribute with the cleaned value
                        $attr->value = $cleanValue;
                    }
                }

                foreach ($attrsToRemove as $attrName) {
                    $child->removeAttribute($attrName);
                }

                // Restrict target to _blank only; remove other values (_top, _parent can enable framing attacks)
                if ($tagName === 'a' && $child->hasAttribute('target')) {
                    if (strtolower($child->getAttribute('target')) !== '_blank') {
                        $child->removeAttribute('target');
                    }
                }

                // Force rel="noopener noreferrer" on links with target="_blank"
                if ($tagName === 'a' && $child->hasAttribute('target')) {
                    $child->setAttribute('rel', 'noopener noreferrer');
                }

                // Recurse into children
                $this->sanitizeNode($child, $allowedTags, $allowedAttributes, $allowedSchemes);
            } elseif ($child instanceof DOMComment) {
                // Remove HTML comments
                $nodesToRemove[] = $child;
            }
        }

        // Remove disallowed elements, replacing them with their text content
        foreach ($nodesToRemove as $removeNode) {
            if ($removeNode instanceof DOMElement) {
                $textContent = $removeNode->textContent;
                if ($textContent !== '') {
                    $textNode = $node->ownerDocument->createTextNode($textContent);
                    $node->replaceChild($textNode, $removeNode);
                } else {
                    $node->removeChild($removeNode);
                }
            } else {
                $node->removeChild($removeNode);
            }
        }
    }
}
