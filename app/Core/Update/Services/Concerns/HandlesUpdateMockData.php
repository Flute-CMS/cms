<?php

namespace Flute\Core\Update\Services\Concerns;

use Flute\Core\App;

trait HandlesUpdateMockData
{
    public function enableMockData(bool $enable): void
    {
        $this->useMockData = $enable;
    }

    protected function incrementVersion(string $version): string
    {
        $parts = explode('.', $version);
        $parts[count($parts) - 1]++;

        return implode('.', $parts);
    }

    private function buildMockData(): array
    {
        $today = date(default_date_format(true));

        $cms = [
            'version' => $this->incrementVersion(App::VERSION),
            'release_date' => $today,
            'tags' => [
                ['type' => 'feature', 'label' => 'Features'],
                ['type' => 'security', 'label' => 'Security'],
            ],
            'changelog' => "# Highlights\n\n- New Dashboard widgets\n- Faster cache engine\n- Security patches\n\n## Details\n- Added support for Early channel\n- Improved UX for updates page",
            'previous_versions' => [
                [
                    'version' => $this->incrementVersion($this->incrementVersion(App::VERSION)),
                    'release_date' => $today,
                    'changelog' => "- Fix minor bugs\n- Improve performance",
                ],
            ],
        ];

        $modules = [
            'shop' => [
                'name' => 'Shop',
                'current_version' => '1.4.0',
                'version' => '1.5.0',
                'release_date' => $today,
                'changelog' => "- New coupons\n- Better analytics",
                'previous_versions' => [
                    ['version' => '1.4.5', 'release_date' => $today, 'changelog' => '- Hotfixes'],
                ],
            ],
            'rules' => [
                'name' => 'Rules',
                'current_version' => '2.0.0',
                'version' => '2.1.0',
                'release_date' => $today,
                'changelog' => "- Rich editor for rules\n- Export to PDF",
            ],
        ];

        $themes = [
            'standard' => [
                'name' => 'Standard Theme',
                'current_version' => '3.2.1',
                'version' => '3.3.0',
                'release_date' => $today,
                'changelog' => "- Polish profile card\n- New color tokens",
            ],
        ];

        return [
            'cms' => $cms,
            'modules' => $modules,
            'themes' => $themes,
        ];
    }
}
