<?php

namespace Flute\Core\Modules\Installer\Controllers\Concerns;

use Flute\Core\Router\Annotations\Route;
use Flute\Core\Support\FluteRequest;
use Throwable;

trait HandlesWelcomeStep
{
    #[Route('/install', name: 'installer.welcome', methods: ['GET'])]
    public function welcome(FluteRequest $request): mixed
    {
        if ($this->installerConfig->isInstalled()) {
            return response()->redirect('/');
        }

        if ($denied = $this->guardInstallerAccess($request)) {
            return $denied;
        }

        $welcomeData = [
            'preferredLanguage' => $this->getPreferredLanguage(),
            'selectedLanguage' => config('lang.locale', 'en'),
            'languages' => config('lang.available'),
            'fluteKey' => config('installer.flute_key', ''),
            'keyError' => null,
            'keyValid' => false,
        ];

        return $this->installerView->render([
            'stepView' => 'installer::yoyo.welcome',
            'stepData' => $welcomeData,
        ]);
    }

    #[Route('/install', name: 'installer.welcome.submit', methods: ['POST'])]
    public function processWelcome(FluteRequest $request): mixed
    {
        if ($this->installerConfig->isInstalled()) {
            return response()->redirect('/');
        }

        if ($denied = $this->guardInstallerAccess($request)) {
            return $denied;
        }

        $action = $request->input('action', '');

        if ($action === 'setLanguage') {
            $language = $request->input('language', 'en');
            $lang = config('lang');
            $lang['locale'] = $language;
            config()->set('lang', $lang);
            config()->save();
            app()->setLang($language);

            $welcomeData = [
                'preferredLanguage' => $this->getPreferredLanguage(),
                'fluteKey' => $request->input('fluteKey', config('installer.flute_key', '')),
                'keyError' => null,
                'keyValid' => false,
                'selectedLanguage' => $language,
                'languages' => config('lang.available'),
            ];

            return $this->installerView->render([
                'stepView' => 'installer::yoyo.welcome',
                'stepData' => $welcomeData,
            ]);
        }

        $fluteKey = $request->input('fluteKey', '');
        $keyError = null;
        $keyValid = false;
        $savedKeys = array_filter([config('app.flute_key', ''), config('installer.flute_key', '')]);

        if (!empty($fluteKey) && !in_array($fluteKey, $savedKeys, true)) {
            try {
                $api = new \Flute\Core\Services\FluteApiClient(timeout: 10, connectTimeout: 5);
                $apiResponse = $api->get('/api/updates', [
                    'query' => ['accessKey' => $fluteKey],
                    'headers' => ['User-Agent' => 'Flute-CMS/' . \Flute\Core\App::VERSION],
                ]);
                $statusCode = $apiResponse->getStatusCode();
                if ($statusCode === 200) {
                    $app = config('app');
                    $app['flute_key'] = $fluteKey;
                    config()->set('app', $app);
                    config()->save();
                    $keyValid = true;
                } elseif ($statusCode === 401) {
                    $keyError = __('install.welcome.key_error');
                } else {
                    $body = json_decode($apiResponse->getBody()->getContents(), true);
                    $keyError = ( is_array($body) ? $body['error'] ?? null : null ) ?? __('install.welcome.key_error');
                }
            } catch (Throwable $e) {
                \logs()->error('License key validation failed: ' . $e->getMessage());
                $keyError = \__('install.welcome.key_error');
            }

            if ($keyError) {
                $welcomeData = [
                    'preferredLanguage' => $this->getPreferredLanguage(),
                    'fluteKey' => $fluteKey,
                    'keyError' => $keyError,
                    'keyValid' => false,
                    'selectedLanguage' => config('lang.locale', 'en'),
                    'languages' => config('lang.available'),
                ];

                return $this->installerView->render([
                    'stepView' => 'installer::yoyo.welcome',
                    'stepData' => $welcomeData,
                ]);
            }
        } elseif (!empty($fluteKey) && in_array($fluteKey, $savedKeys, true)) {
            $keyValid = true;
        }

        $this->installerConfig->setCurrentStep(1);
        config()->save();

        return response()->redirect(route('installer.step', ['id' => 1]));
    }
}
