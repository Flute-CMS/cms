<?php

namespace Flute\Core\Modules\Page\Widgets;

use Flute\Core\Markdown\Parser;

class EditorWidget extends AbstractWidget
{
    public function getName(): string
    {
        return 'widgets.editor';
    }

    public function getIcon(): string
    {
        return 'ph.regular.pencil';
    }

    public function getSettings(): array
    {
        return [
            'content' => '',
            'inCard' => false,
        ];
    }

    public function hasSettings(): bool
    {
        return true;
    }

    public function renderSettingsForm(array $settings): string|bool
    {
        return view('flute::widgets.settings.editor', [
            'settings' => $settings,
            'inCard' => $settings['inCard'] ?? false,
        ])->render();
    }

    public function saveSettings(array $input): array
    {
        return [
            'content' => $input['content'] ?? '',
            'inCard' => isset($input['inCard']) ? true : false,
        ];
    }

    public function render(array $settings): string|null
    {
        $content = $settings['content'] ?? '';
        $html = (new Parser())->parse($content, false, false);

        return view('flute::widgets.editor', [
            'html' => $html,
            'inCard' => $settings['inCard'] ?? false,
        ])->render();
    }

    public function getCategory(): string
    {
        return 'general';
    }

    public function getDefaultWidth(): int
    {
        return 12;
    }
}
