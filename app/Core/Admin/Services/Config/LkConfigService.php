<?php

namespace Flute\Core\Admin\Services\Config;

use Flute\Core\Admin\Support\AbstractConfigService;
use Symfony\Component\HttpFoundation\Response;

class LkConfigService extends AbstractConfigService
{
    public function updateConfig(array $params): Response
    {
        $config = [
            "min_amount" => (int) $params['min_amount'],
            "currency_view" => $params['currency_view'],
            "oferta_view" => $this->b($params['oferta_view']),
        ];

        try {
            $this->fileSystemService->updateConfig($this->getConfigPath('lk'), $config);
            return response()->success(__('def.success'));
        } catch (\Exception $e) {
            return response()->error(500, __('def.unknown_error'));
        }
    }
}
