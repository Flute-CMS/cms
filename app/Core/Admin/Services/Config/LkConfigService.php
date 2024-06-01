<?php

namespace Flute\Core\Admin\Services\Config;

use Flute\Core\Admin\Support\AbstractConfigService;
use Symfony\Component\HttpFoundation\Response;

class LkConfigService extends AbstractConfigService
{
    public function updateConfig(array $params): Response
    {
        $config = [
            // "min_amount" => (int) $params['min_amount'],
            "currency_view" => $params['currency_view'],
            "oferta_view" => $this->b($params['oferta_view']),
            "pay_in_new_window" => $this->b($params['pay_in_new_window']),
        ];

        try {
            $this->fileSystemService->updateConfig($this->getConfigPath('lk'), $config);
            user()->log('events.config_updated', 'lk');

            return response()->success(__('def.success'));
        } catch (\Exception $e) {
            logs()->error($e);
            return response()->error(500, $e->getMessage() ?? __('def.unknown_error'));
        }
    }
}
