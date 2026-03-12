<?php

namespace Flute\Admin\Platform\Fields;

/**
 * Translatable rich text editor — wraps TipTap with language tabs.
 * Falls back to regular RichText when only one language is available.
 *
 * Usage:
 *   TranslatableRichText::make('content')
 *       ->height(400)
 *       ->enableImageUpload()
 *       ->value($widget->content)  // JSON string or plain HTML
 */
class TranslatableRichText extends RichText
{
    protected $view = 'admin::partials.fields.translatable-richtext';
}
