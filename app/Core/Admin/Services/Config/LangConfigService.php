<?php

namespace Flute\Core\Admin\Services\Config;

use Flute\Core\Admin\Support\AbstractConfigService;
use Symfony\Component\HttpFoundation\Response;

class LangConfigService extends AbstractConfigService
{
    public function updateConfig(array $params): Response
    {
        $config = [
            "locale" => $params['locale'],
            "cache" => $this->b($params['cache']),
            "available" => app('lang.available')
        ];

        try {
            $this->fileSystemService->updateConfig($this->getConfigPath('lang'), $config);
            return response()->success(__('def.success'));
        } catch (\Exception $e) {
            return response()->error(500, __('def.unknown_error'));
        }
    }
}
