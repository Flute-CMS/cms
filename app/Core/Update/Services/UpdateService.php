<?php

namespace Flute\Core\Update\Services;

use Flute\Core\Markdown\Parser;
use Flute\Core\ModulesManager\ModuleManager;
use Flute\Core\Theme\ThemeManager;
use Flute\Core\Update\Services\Concerns\HandlesChannelCatalog;
use Flute\Core\Update\Services\Concerns\HandlesUpdateDownload;
use Flute\Core\Update\Services\Concerns\HandlesUpdateFetching;
use Flute\Core\Update\Services\Concerns\HandlesUpdateInventory;
use Flute\Core\Update\Services\Concerns\HandlesUpdateMockData;

class UpdateService
{
    use HandlesChannelCatalog;
    use HandlesUpdateDownload;
    use HandlesUpdateFetching;
    use HandlesUpdateInventory;
    use HandlesUpdateMockData;

    private const CACHE_KEY = 'flute_updates';
    private const CACHE_DURATION = 1440;

    private const TRUSTED_FLUTE_DOWNLOAD_HOSTS = [
        'flute-cms.com',
        'api.flute-cms.com',
        'market.flute-cms.com',
        'mirror.flute-cms.com',
    ];

    protected ModuleManager $moduleManager;
    protected ThemeManager $themeManager;
    protected bool $useMockData = false;

    /**
     * Selected update channel: stable|early
     */
    protected string $channel = 'stable';

    protected Parser $markdownParser;

    public function __construct(
        ModuleManager $moduleManager,
        ThemeManager $themeManager,
        ?Parser $markdownParser = null,
    ) {
        $this->moduleManager = $moduleManager;
        $this->themeManager = $themeManager;
        $this->markdownParser = $markdownParser ?? new Parser();
    }
}
