<?php

namespace Flute\Core\Admin\Services\Config;

use Flute\Core\Admin\Support\AbstractConfigService;
use Symfony\Component\HttpFoundation\Response;

class ProfileConfigService extends AbstractConfigService
{
    public function updateConfig(array $params): Response
    {
        $config = array_merge(config('profile'), [
            "max_banner_size" => (int) $params['maxBannerSize'],
            "max_avatar_size" => (int) $params['maxAvatarSize'],
            "banner_types" => array_keys(array_filter($params['banner_types'], function ($value) {
                return $this->b($value);
            })),
            "avatar_types" => array_keys(array_filter($params['avatar_types'], function ($value) {
                return $this->b($value);
            })),
            "convert_to_webp" => $this->b($params['convertToWebp']),
            "change_uri" => $this->b($params['changeUri'])
        ]);

        try {
            $this->fileSystemService->updateConfig($this->getConfigPath('profile'), $config);
            return response()->success(__('def.success'));
        } catch (\Exception $e) {
            return response()->error(500, __('def.unknown_error'));
        }
    }
}
