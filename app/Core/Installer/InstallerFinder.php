<?php

namespace Flute\Core\Installer;

class InstallerFinder
{
    public function config(string $arg = null, string $default = null)
    {
        return $arg ? config('installer')[$arg] ?? $default : config('installer');
    }

    public function setDomain()
    {
        if (config('app.url') === '') {
            $app = config('app');
            $app['url'] = $this->url();
            fs()->updateConfig(BASE_PATH . 'config/app.php', $app);

            config()->set('app.url', $this->url());
        }
    }

    protected function url()
    {
        return request()->isSecure() ? 'https' : 'http' . "://" . request()->getHttpHost();
    }

    public function setLocale()
    {
        if ($lang = config('installer.params.lang', 'en'))
            translation()->setLocale($lang);
    }

    public function isInstalled(): bool
    {
        return (bool) $this->config('finished');
    }
}