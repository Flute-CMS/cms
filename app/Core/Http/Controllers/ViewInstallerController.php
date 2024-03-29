<?php

namespace Flute\Core\Http\Controllers;

use Flute\Core\Services\FormService;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Nette\Forms\Form;
use Nette\Utils\Html;
use Symfony\Component\HttpFoundation\Response;

class ViewInstallerController extends AbstractController
{
    public function installView(FluteRequest $fluteRequest, $id)
    {
        return call_user_func_array([$this, "__{$id}step"], [
            $fluteRequest
        ]);
    }

    protected function __1step(FluteRequest $fluteRequest): Response
    {
        return view("Core/Http/Views/Installer/steps/1.blade.php");
    }

    protected function __2step(FluteRequest $fluteRequest): Response
    {
        return view("Core/Http/Views/Installer/steps/2.blade.php", [
            "exts" => $this->extsForStepTwo(),
            "reqs" => $this->reqsForStepTwo()
        ]);
    }

    protected function __3step(FluteRequest $fluteRequest): Response
    {
        return view("Core/Http/Views/Installer/steps/3.blade.php", [
            'form' => $this->getThirdForm()
        ]);
    }

    protected function __4step(FluteRequest $fluteRequest): Response
    {
        return view("Core/Http/Views/Installer/steps/4.blade.php");
    }

    protected function __5step(FluteRequest $fluteRequest): Response
    {
        return view("Core/Http/Views/Installer/steps/5.blade.php", [
            'form' => $this->getFourForm()
        ]);
    }

    protected function __6step(FluteRequest $fluteRequest): Response
    {
        return view("Core/Http/Views/Installer/steps/6.blade.php");
    }

    protected function __7step(FluteRequest $fluteRequest): Response
    {
        return view("Core/Http/Views/Installer/steps/7.blade.php");
    }

    protected function extsForStepTwo()
    {
        $extensions = array(
            "pdo" => false,
            "pdo_mysql" => false,
            "mysqli" => false,
            "mbstring" => false,
            "json" => false,
            "curl" => false,
            "gd" => false,
            "intl" => false,
            "xml" => false,
            "zip" => false,
            "gmp" => false,
            "dom" => false,
            "iconv" => false,
            "simplexml" => false,
            "fileinfo" => false,
            "tokenizer" => false,
            "ctype" => false,
            "session" => false,
            "bcmath" => false,
            "openssl" => false,
        );

        $load_exts = [];

        $bad = 0;

        $recommended = ["dom", "gd", "intl", "iconv", "simplexml", "fileinfo", "tokenizer", "ctype", "session", "bcmath", "openssl"];

        foreach ($extensions as $extension => $loaded) {
            if (extension_loaded($extension)) {
                $load_exts[$extension] = [
                    "type" => "loaded"
                ];
            } else if (in_array($extension, $recommended)) {
                $load_exts[$extension] = [
                    "type" => "recommended"
                ];
            } else {
                $load_exts[$extension] = [
                    "type" => "disabled"
                ];

                $bad++;
            }
        }

        // Сортировка расширений
        uasort($load_exts, function ($a, $b) {
            $order = [
                "disabled" => 0,
                "recommended" => 1,
                "loaded" => 2
            ];

            return $order[$a['type']] - $order[$b['type']];
        });

        return [
            "list" => $load_exts,
            "bad" => $bad,
        ];
    }

    protected function reqsForStepTwo()
    {
        $requirements = array(
            "php_version" => "7.4",
            "web_server" => "nginx",
            "opcache_enabled" => false,
            // Другие требования...
        );

        $check_results = [];

        $phpVersion = phpversion();
        $webServer = $_SERVER['SERVER_SOFTWARE'];
        $opcacheEnabled = function_exists('opcache_get_status') ? @opcache_get_status() : null;

        // Проверка версии PHP
        $check_results['php_version'] = [
            "required" => version_compare($phpVersion, $requirements['php_version'], '>='),
            "current" => $phpVersion
        ];

        // Проверка веб-сервера
        $check_results['web_server'] = [
            "required" => stripos($webServer, $requirements['web_server']) !== false,
            "current" => $webServer
        ];

        // Проверка включенного opcache
        $check_results['opcache_enabled'] = [
            "required" => !$opcacheEnabled,
            "current" => (bool) $opcacheEnabled
        ];

        return $check_results;
    }

    protected function getThirdForm(): FormService
    {

        /** @var FormService $form */
        $form = form();

        $form->setHtmlAttribute('id', 'form');

        $form->addSelect('driver', __('install.3.driver'), [
            'mysql' => 'MySQL',
            'pgsql' => 'PostgreSQL',
        ])->setRequired()
            ->setOption('col-md', '6');

        $form->addText('host', __('install.3.ip'))
            ->setRequired()
            ->setOption('col-md', '6');

        $form->addText('port', __('install.3.port'))
            ->setRequired()
            ->setType('number')
            ->setDefaultValue(3306)
            ->setOption('col-md', '6');

        $form->addText('db', __('install.3.db'))
            ->setRequired()
            ->setOption('col-md', '6');

        $form->addText('user', __('install.3.user'))
            ->setRequired()
            ->setOption('col-md', '6');

        $form->addPassword('pass', __('install.3.pass'))
            ->setOption('col-md', '6');

        return $form;
    }

    protected function getFourForm(): FormService
    {

        /** @var FormService $form */
        $form = form();

        $form->setHtmlAttribute('id', 'form');

        $form->addText('login', __('install.5.login'))
            ->setRequired()
            ->setOption('col-md', '12');

        $form->addText('name', __('install.5.name'))
            ->setRequired()
            ->setOption('col-md', '6');

        $form->addEmail('email', __('install.5.email'))->setRequired(__('auth.registration.enter_email'))
            ->addRule(
                    $form::Email,
                __('auth.registration.invalid_email')
            )
            ->setOption('col-md', '6');

        $form->addPassword('password', __('auth.registration.password'))->setRequired(__('auth.registration.enter_password'))->setOption('col-md', '6');

        $form->addPassword('password_confirmation', __('auth.registration.password_confirmation'))
            ->setRequired(__('auth.registration.enter_password_confirmation'))
            ->addRule($form::Equal, __('auth.registration.enter_password_confirmation_incorrect'), $form['password'])
            ->addRule($form::MIN_LENGTH, __('auth.registration.password_min_length', ['length' => config('auth.validation.password.min_length')]), config('auth.validation.password.min_length'))
            ->addRule($form::MAX_LENGTH, __('auth.registration.password_max_length', ['length' => config('auth.validation.password.min_length')]), config('auth.validation.password.max_length'))
            ->setOmitted()
            ->setOption('col-md', '6');

        return $form;
    }
}