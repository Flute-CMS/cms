<?php

namespace Flute\Admin\Packages\Marketplace\Services;

use Flute\Admin\Packages\Marketplace\Services\Concerns\HandlesComposerUpdate;
use Flute\Admin\Packages\Marketplace\Services\Concerns\HandlesMarketplaceLock;
use Flute\Admin\Packages\Marketplace\Services\Concerns\HandlesModuleDownload;
use Flute\Admin\Packages\Marketplace\Services\Concerns\HandlesModuleExtraction;
use Flute\Admin\Packages\Marketplace\Services\Concerns\HandlesModuleFs;
use Flute\Admin\Packages\Marketplace\Services\Concerns\HandlesModuleInstall;
use Flute\Admin\Packages\Marketplace\Services\Concerns\HandlesModuleValidation;
use Flute\Core\ModulesManager\Actions\Concerns\FlushesTranslationCache;
use GuzzleHttp\Client;

class ModuleInstallerService
{
    use FlushesTranslationCache;
    use HandlesComposerUpdate;
    use HandlesMarketplaceLock;
    use HandlesModuleDownload;
    use HandlesModuleExtraction;
    use HandlesModuleFs;
    use HandlesModuleInstall;
    use HandlesModuleValidation;

    private const ZIP_MAGIC_LOCAL = "PK\x03\x04";
    private const ZIP_MAGIC_EMPTY = "PK\x05\x06";
    private const ZIP_MAGIC_SPANNED = "PK\x07\x08";

    /**
     * Official Flute marketplace hosts (always merged with config mirrors).
     */
    private const TRUSTED_FLUTE_DOWNLOAD_HOSTS = [
        'flute-cms.com',
        'api.flute-cms.com',
        'mirror.flute-cms.com',
    ];

    protected Client $client;
    protected string $tempDir;
    protected string $modulesDir;

    protected ?string $moduleArchivePath = null;
    protected ?string $moduleExtractPath = null;
    protected ?string $moduleKey = null;
    protected ?array $moduleInfo = null;
    protected ?string $backupDir = null;
    protected ?string $moduleFolder = null;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 600.0,
            'connect_timeout' => 25.0,
            'http_errors' => false,
            'verify' => true,
            'allow_redirects' => [
                'max' => 5,
                'strict' => false,
                'referer' => true,
                'track_redirects' => false,
            ],
        ]);

        $this->tempDir = storage_path('app/temp/marketplace');
        $this->modulesDir = path('app/Modules');

        $this->ensureWritableDirectory($this->tempDir, true);
    }
}
