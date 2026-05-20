<?php

namespace Flute\Core\Modules\Installer\Controllers\Concerns;

use Flute\Core\Router\Annotations\Route;
use Flute\Core\Support\FluteRequest;
use Throwable;

trait HandlesCustomizeStep
{
    #[Route('/install/4', name: 'installer.step4.save', methods: ['POST'])]
    public function saveLanguages(FluteRequest $request): mixed
    {
        if ($this->installerConfig->isInstalled()) {
            return response()->redirect('/');
        }

        if ($denied = $this->guardInstallerAccess($request)) {
            return $denied;
        }

        try {
            $languages = $request->input('languages', []);
            $languages = array_values(array_unique(array_filter($languages)));

            $currentLocale = config('lang.locale', 'en');
            if (!in_array($currentLocale, $languages, true)) {
                $languages[] = $currentLocale;
            }

            $langConfig = config('lang');
            $langConfig['available'] = $languages;
            config()->set('lang', $langConfig);
            config()->save();

            $this->installerConfig->setCurrentStep(5);
            config()->save();

            return $this->renderStepWithPush(5);
        } catch (Throwable $e) {
            return $this->renderStep(4, [
                'errorMessage' => $e->getMessage(),
            ]);
        }
    }

    #[Route('/install/5', name: 'installer.step5.save', methods: ['POST'])]
    public function saveModules(FluteRequest $request): mixed
    {
        if ($this->installerConfig->isInstalled()) {
            return response()->redirect('/');
        }

        if ($denied = $this->guardInstallerAccess($request)) {
            return $denied;
        }

        try {
            $modules = $request->input('modules', []);
            $modules = array_values(array_filter($modules));
            $this->installerConfig->setParam('selected_modules', $modules);

            $this->installerConfig->setCurrentStep(6);
            config()->save();

            return $this->renderStepWithPush(6);
        } catch (Throwable $e) {
            return $this->renderStep(5, [
                'errorMessage' => $e->getMessage(),
            ]);
        }
    }

    protected function getLanguagesData(): array
    {
        $flagOverrides = [
            'cs' => 'cz',
            'da' => 'dk',
            'el' => 'gr',
            'he' => 'il',
            'hi' => 'in',
            'ko' => 'kr',
            'ta' => 'in',
            'bn' => 'bd',
            'ms' => 'my',
        ];

        $locale = config('lang.locale', 'en');
        $langsFile = path("i18n/{$locale}/langs.php");

        if (!file_exists($langsFile)) {
            $langsFile = path('i18n/en/langs.php');
        }

        $langsMap = file_exists($langsFile) ? ( require $langsFile ) : [];

        $allLangs = array_keys($langsMap);
        $enabledLangs = config('lang.available', []);
        $currentLocale = config('lang.locale', 'en');

        $allLanguages = [];
        foreach ($allLangs as $code) {
            $langKey = "langs.{$code}";
            $translated = __($langKey);
            $name = $translated !== $langKey ? $translated : $langsMap[$code] ?? strtoupper($code);
            $flagCode = $flagOverrides[$code] ?? $code;

            $allLanguages[] = [
                'code' => $code,
                'native' => $name,
                'flag' => $flagCode,
            ];
        }

        usort($allLanguages, static function ($a, $b) use ($currentLocale) {
            if ($a['code'] === $currentLocale) {
                return -1;
            }
            if ($b['code'] === $currentLocale) {
                return 1;
            }

            return 0;
        });

        return [
            'allLanguages' => $allLanguages,
            'enabledLanguages' => $enabledLangs,
            'errorMessage' => null,
        ];
    }

    protected function getModulesData(): array
    {
        $modules = [];
        $recommended = [];
        $modulesError = null;
        $noKey = false;
        $fluteKey = config('app.flute_key', '');
        $recommendedSlugs = ['Monitoring', 'SteamFriends', 'MiniBalance', 'SteamInfo', 'SteamProfile', 'API'];

        if (empty($fluteKey)) {
            $noKey = true;
        } else {
            try {
                $api = new \Flute\Core\Services\FluteApiClient(timeout: 10, connectTimeout: 5);

                $response = $api->get('/api/external/modules', [
                    'query' => [
                        'accessKey' => $fluteKey,
                        'php' => substr(PHP_VERSION, 0, 3),
                    ],
                ]);

                if ($response->getStatusCode() === 200) {
                    $body = json_decode($response->getBody()->getContents(), true);
                    $allModules = is_array($body) ? $body : [];

                    $allModules = array_filter($allModules, static fn($m) => empty($m['isPaid']));

                    foreach ($allModules as $module) {
                        $slug = $module['name'] ?? $module['slug'] ?? '';
                        if (in_array($slug, $recommendedSlugs, true)) {
                            $recommended[] = $module;
                        } else {
                            $modules[] = $module;
                        }
                    }

                    usort($recommended, static function ($a, $b) use ($recommendedSlugs) {
                        $aIdx = array_search($a['name'] ?? $a['slug'] ?? '', $recommendedSlugs);
                        $bIdx = array_search($b['name'] ?? $b['slug'] ?? '', $recommendedSlugs);

                        return $aIdx - $bIdx;
                    });

                    usort($modules, static fn($a, $b) => strcasecmp(
                        $a['name'] ?? $a['slug'] ?? '',
                        $b['name'] ?? $b['slug'] ?? '',
                    ));
                } else {
                    $modulesError = \__('install.modules.fetch_error');
                }
            } catch (Throwable $e) {
                $modulesError = \__('install.modules.fetch_error');
            }
        }

        return [
            'recommended' => $recommended,
            'modules' => $modules,
            'modulesError' => $modulesError,
            'noKey' => $noKey,
            'errorMessage' => null,
        ];
    }
}
