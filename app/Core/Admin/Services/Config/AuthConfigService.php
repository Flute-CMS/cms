<?php

namespace Flute\Core\Admin\Services\Config;

use Flute\Core\Admin\Support\AbstractConfigService;
use Symfony\Component\HttpFoundation\Response;

class AuthConfigService extends AbstractConfigService
{
    public function updateConfig(array $params): Response
    {
        $config = array_merge(config('auth'), [
            "remember_me" => $this->b($params['rememberMe']),
            "remember_me_duration" => (int) $params['rememberMeDuration'],
            "csrf_enabled" => $this->b($params['csrfEnabled']),
            "reset_password" => $this->b($params['resetPassword']),
            "security_token" => $this->b($params['securityToken']),
            "registration" => [
                "confirm_email" => $this->b($params['confirmEmail']),
                "social_supplement" => $this->b($params['socialSupplement'])
            ],
            "validation" => [
                "login" => [
                    "min_length" => (int) $params['loginMinLength'],
                    "max_length" => (int) $params['loginMaxLength']
                ],
                "password" => [
                    "min_length" => (int) $params['passwordMinLength'],
                    "max_length" => (int) $params['passwordMaxLength']
                ],
                "name" => [
                    "min_length" => (int) $params['nameMinLength'],
                    "max_length" => (int) $params['nameMaxLength']
                ]
            ]
        ]);

        try {
            $this->fileSystemService->updateConfig($this->getConfigPath('auth'), $config);
            return response()->success(__('def.success'));
        } catch (\Exception $e) {
            return response()->error(500, __('def.unknown_error'));
        }
    }
}
