<?php

namespace Flute\Core\Admin\Services\Config;

use Flute\Core\Admin\Support\AbstractConfigService;
use Symfony\Component\HttpFoundation\Response;

class AppConfigService extends AbstractConfigService
{
    public function updateConfig(array $params): Response
    {
        $config = array_merge(config('app'), [
            "name" => $params['name'],
            "url" => $params['url'],
            "steam_api" => $params['steam_api'],
            "debug" => $this->b($params['debug']),
            "debug_ips" => $this->parseDebugIps($params['debugIps'] ?? ''),
            "key" => $params['key'],
            "tips" => $this->b($params['tips']),
            "timezone" => $params['timezone'],
            "notifications" => $params['notifications'],
            "mode" => $this->b($params['performanceMode']) === true ? 'performance' : 'default',
            "share" => $this->b($params['share']),
            "flute_copyright" => $this->b($params['flute_copyright']),
        ]);

        try {
            $this->fileSystemService->updateConfig($this->getConfigPath('app'), $config);
            return response()->success(__('def.success'));
        } catch (\Exception $e) {
            return response()->error(500, __('def.unknown_error'));
        }
    }

    protected function parseDebugIps(string $ips): array
    {
        return array_filter(array_map('trim', explode(',', $ips)));
    }
}
