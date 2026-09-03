<?php

namespace Flute\Core\Modules\Auth\Services;

use Flute\Core\Modules\Auth\Contracts\SocialServiceInterface;
use Flute\Core\Modules\Auth\Services\Concerns\HandlesProviderInit;
use Flute\Core\Modules\Auth\Services\Concerns\HandlesProviderLookup;
use Flute\Core\Modules\Auth\Services\Concerns\HandlesProviderRegistration;
use Flute\Core\Modules\Auth\Services\Concerns\HandlesProviderSettings;
use Flute\Core\Modules\Auth\Services\Concerns\HandlesSocialAuth;
use Flute\Core\Modules\Auth\Services\Concerns\HandlesSocialProfileSync;
use Hybridauth\Hybridauth;

class SocialService implements SocialServiceInterface
{
    use HandlesProviderInit;
    use HandlesProviderLookup;
    use HandlesProviderRegistration;
    use HandlesProviderSettings;
    use HandlesSocialAuth;
    use HandlesSocialProfileSync;

    /** @var Hybridauth */
    private $hybridauth;

    private array $registeredProviders = [];

    /**
     * Settings that should be stored on provider root level, not inside "keys".
     */
    private array $nonKeySettingFields = ['scope', 'fields', 'display', 'version', 'service_token', 'proxy'];

    public function __construct()
    {
        $this->initializeProviders();
        $this->overrideDefaultProviders();
    }
}
