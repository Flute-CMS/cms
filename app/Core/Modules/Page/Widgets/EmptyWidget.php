<?php

namespace Flute\Core\Modules\Page\Widgets;

class EmptyWidget extends AbstractWidget
{
    public function getName(): string
    {
        return 'widgets.empty';
    }

    public function getIcon(): string
    {
        return 'ph.regular.smiley-melting';
    }

    public function hasSettings(): bool
    {
        return true;
    }

    public function renderSettingsForm(array $settings): string|bool
    {
        $height = $settings['height'] ?? '100';

        return view('flute::widgets.settings.empty', [
            'settings' => $settings,
            'height' => $height,
        ])->render();
    }

    public function saveSettings(array $input): array
    {
        $height = isset($input['height']) ? trim($input['height']) : '100';

        return [
            'height' => $height,
        ];
    }

    public function getSettings(): array
    {
        return [
            'height' => '100',
        ];
    }

    public function render(array $settings): string|null
    {
        return view('flute::widgets.empty', ['height' => $settings['height'] ?? '100'])->render();
    }

    public function getCategory(): string
    {
        return 'other';
    }
}
