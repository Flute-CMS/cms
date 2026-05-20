<?php

namespace Flute\Core\Modules\Installer\Controllers\Concerns;

use Flute\Core\Database\Entities\Role;
use Flute\Core\Database\Entities\User;
use Flute\Core\Router\Annotations\Route;
use Flute\Core\Support\FluteRequest;
use Throwable;

trait HandlesAccountSiteStep
{
    #[Route('/install/3', name: 'installer.step3.save', methods: ['POST'])]
    public function saveAccountAndSite(FluteRequest $request): mixed
    {
        if ($this->installerConfig->isInstalled()) {
            return response()->redirect('/');
        }

        if ($denied = $this->guardInstallerAccess($request)) {
            return $denied;
        }

        $errorMessage = null;

        try {
            $name = $request->input('name', '');
            $email = $request->input('email', '');
            $login = $request->input('login', '');
            $password = $request->input('password', '');
            $passwordConfirmation = $request->input('password_confirmation', '');
            $siteName = $request->input('siteName', 'Flute');
            $siteDescription = $request->input('siteDescription', '');
            $siteUrl = $request->input('siteUrl', '');
            $timezone = $request->input('timezone', 'UTC');
            $siteKeywords = $request->input('siteKeywords', 'Flute, game servers, gaming');

            $adminData = [
                'name' => $name,
                'email' => $email,
                'login' => $login,
                'password' => $password,
                'password_confirmation' => $passwordConfirmation,
            ];

            $adminRules = [
                'name' => 'required|human-name|min-str-len:3|max-str-len:255',
                'email' => 'required|email|max-str-len:255',
                'login' => 'required|regex:/^[a-zA-Z0-9._-]+$/|min-str-len:6|max-str-len:20',
                'password' => 'required|min-str-len:8|confirmed',
                'password_confirmation' => 'required',
            ];

            $validated = $this->validate($adminData, $adminRules);

            if ($validated !== true) {
                if ($this->installerConfig->getParams('admin_user_exists')) {
                    return response()->redirect(route('installer.step', ['id' => 4]));
                }

                return $this->renderStep(3, [
                    'errorMessage' => is_object($validated) && method_exists($validated, 'getErrors')
                        ? implode(', ', $validated->getErrors()->all())
                        : __('install.account_site.error_validation'),
                    'name' => $name,
                    'email' => $email,
                    'login' => $login,
                    'siteName' => $siteName,
                    'siteDescription' => $siteDescription,
                    'siteUrl' => $siteUrl,
                    'timezone' => $timezone,
                    'siteKeywords' => $siteKeywords,
                ]);
            }

            $siteData = [
                'siteName' => $siteName,
                'siteUrl' => $siteUrl,
                'timezone' => $timezone,
            ];

            $siteRules = [
                'siteName' => 'required|max-str-len:100',
                'siteUrl' => 'required|url',
                'timezone' => 'required|timezone',
            ];

            $validatedSite = $this->validate($siteData, $siteRules);

            if ($validatedSite !== true) {
                return $this->renderStep(3, [
                    'errorMessage' => is_object($validatedSite) && method_exists($validatedSite, 'getErrors')
                        ? implode(', ', $validatedSite->getErrors()->all())
                        : __('install.account_site.error_validation'),
                    'name' => $name,
                    'email' => $email,
                    'login' => $login,
                    'siteName' => $siteName,
                    'siteDescription' => $siteDescription,
                    'siteUrl' => $siteUrl,
                    'timezone' => $timezone,
                    'siteKeywords' => $siteKeywords,
                ]);
            }

            $existingUser = null;

            try {
                $existingUser = User::findOne(['login' => $login]);
            } catch (Throwable $e) {
                logs('installer')->debug('Installer user lookup skipped before schema is ready', [
                    'message' => $e->getMessage(),
                ]);
            }

            if ($existingUser) {
                $existingUser->name = $name;
                $existingUser->email = $email;
                $existingUser->setPassword($password);
                $existingUser->save();
            } else {
                $user = new User();
                $user->name = $name;
                $user->email = $email;
                $user->login = $login;
                $user->avatar = config('profile.default_avatar');
                $user->banner = config('profile.default_banner');
                $user->setPassword($password);
                $user->verified = true;

                $adminRole = Role::findOne(['name' => 'admin']);

                if ($adminRole) {
                    $user->addRole($adminRole);
                }

                $user->save();
            }

            $this->installerConfig->setParams([
                'admin_user_exists' => true,
                'admin_name' => $name,
                'admin_email' => $email,
                'admin_login' => $login,
            ]);

            $appConfig = config('app');
            $appConfig['name'] = $siteName;
            $appConfig['description'] = $siteDescription;
            $appConfig['keywords'] = $siteKeywords;
            $appConfig['url'] = $siteUrl;
            $appConfig['timezone'] = $timezone;

            config()->set('app', $appConfig);
            config()->save();

            $this->installerConfig->setCurrentStep(4);
            config()->save();

            return response()->redirect(route('installer.step', ['id' => 4]));
        } catch (Throwable $e) {
            $errorMessage = $e->getMessage();
        }

        return $this->renderStep(3, [
            'errorMessage' => $errorMessage,
            'name' => $request->input('name', ''),
            'email' => $request->input('email', ''),
            'login' => $request->input('login', ''),
            'siteName' => $request->input('siteName', 'Flute'),
            'siteDescription' => $request->input('siteDescription', ''),
            'siteUrl' => $request->input('siteUrl', ''),
            'timezone' => $request->input('timezone', 'UTC'),
            'siteKeywords' => $request->input('siteKeywords', ''),
        ]);
    }

    protected function getAccountSiteData(): array
    {
        $appConfig = config('app');

        $siteUrl = $appConfig['url'] ?? '';
        if (empty($siteUrl)) {
            $protocol =
                !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
                || (int) ( $_SERVER['SERVER_PORT'] ?? 80 ) === 443
                    ? 'https://'
                    : 'http://';
            $siteUrl = $protocol . ( $_SERVER['HTTP_HOST'] ?? 'localhost' );
        }

        return [
            'name' => $this->installerConfig->getParams('admin_name', ''),
            'email' => $this->installerConfig->getParams('admin_email', ''),
            'login' => $this->installerConfig->getParams('admin_login', ''),
            'siteName' => $appConfig['name'] ?? 'Flute',
            'siteDescription' => $appConfig['description'] ?? '',
            'siteUrl' => $siteUrl,
            'timezone' => $appConfig['timezone'] ?? 'UTC',
            'siteKeywords' => $appConfig['keywords'] ?? 'Flute, game servers, gaming',
            'timezones' => $this->getTimezones(),
            'errorMessage' => null,
        ];
    }
}
