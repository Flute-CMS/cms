<?php 

namespace Flute\Core\Installer;

class InstallerFinder
{
    public function config( string $arg = null, string $default = null )
    {
        return $arg ? config('installer')[$arg] ?? $default : config('installer');
    }

    public function setDomain()
    {
        if( config('app.url') === '' ) {
            $app = config('app');
            $app['url'] = request()->getSchemeAndHttpHost();
            fs()->updateConfig(BASE_PATH . 'config/app.php', $app);

            config()->set('app.url', request()->getSchemeAndHttpHost());
        }
    }

    public function setLocale()
    {
        if( $lang = config('installer.params.lang', 'en') )
            translation()->setLocale($lang);
    }

    public function isInstalled(): bool
    {
        return (bool) $this->config('finished');
    }
}