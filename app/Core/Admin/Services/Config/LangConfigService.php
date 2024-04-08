<?php

namespace Flute\Core\Admin\Services\Config;

use Flute\Core\Admin\Support\AbstractConfigService;
use Symfony\Component\HttpFoundation\Response;

class LangConfigService extends AbstractConfigService
{
    public function updateConfig(array $params): Response
    {
        $available = $params['available'];

        if( sizeof($available) === 0 ) return response()->error(403, __('admin.form_lang.minimum_one'));

        $availableNormal = [];

        foreach($available as $lang => $val) {
            if( filter_var($val, FILTER_VALIDATE_BOOLEAN) === true )
                $availableNormal[] = $lang;
        }

        $config = [
            "locale" => $params['locale'],
            "cache" => $this->b($params['cache']),
            "available" => $availableNormal,
            "all" => config('lang.all')
        ];

        try {
            $this->fileSystemService->updateConfig($this->getConfigPath('lang'), $config);
            user()->log('events.config_updated', 'lang');

            return response()->success(__('def.success'));
        } catch (\Exception $e) {
            logs()->error($e);
            return response()->error(500, $e->getMessage() ?? __('def.unknown_error'));
        }
    }
}
