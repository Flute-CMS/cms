<?php

namespace Flute\Core\Template\Concerns\TemplateCore;

use Clickfwd\Yoyo\Blade\YoyoServiceProvider;
use Clickfwd\Yoyo\ViewProviders\BladeViewProvider;
use Clickfwd\Yoyo\Yoyo;
use Flute\Core\Modules\Icons\Components\IconComponent;
use Flute\Core\Modules\Icons\Services\IconFinder;
use Flute\Core\Template\Events\TemplateInitialized;
use Flute\Core\Template\FluteBladeApplication;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\ViewErrorBag;
use Jenssegers\Blade\Blade;

trait HandlesBladeSetup
{
    public function addDirective(string $name, callable $function): void
    {
        $this->blade->directive($name, $function);
    }

    protected function setupBlade(): void
    {
        $this->fluteBladeApp = FluteBladeApplication::getInstance();

        $this->fluteBladeApp->bind(\Illuminate\Contracts\Foundation\Application::class, FluteBladeApplication::class);

        $this->fluteBladeApp->alias('view', ViewFactory::class);

        $this->blade = new Blade($this->viewsPath, $this->cachePath, $this->fluteBladeApp);

        $this->blade->if('auth', static fn() => user()->isLoggedIn());
        $this->blade->if('guest', static fn() => !user()->isLoggedIn());
        $this->blade->if('can', static fn($ability, $arguments = []) => user()->can($ability));
        $this->blade->if('cannot', static fn($ability, $arguments = []) => !user()->can($ability));

        $this->blade->directive('asset', static fn($expression) => "<?php echo asset({$expression}); ?>");
        $this->blade->directive('lang', static fn($expression) => "<?php echo __({$expression}); ?>");

        $this->addGlobal('app', app());

        $this->fluteBladeApp->bind('view', fn() => $this->blade);

        ( new YoyoServiceProvider($this->fluteBladeApp) )->boot();

        $this->yoyo = new Yoyo($this->fluteBladeApp);

        $this->yoyo->configure([
            'url' => url($this->isAdminPath() ? self::LIVE_COMPONENT_ADMIN_PATH : self::LIVE_COMPONENT_PATH),
            'scriptsPath' => url('assets/js/htmx/'),
            'historyEnabled' => true,
        ]);

        $this->yoyo->registerViewProvider(fn() => new BladeViewProvider($this->blade));

        if (!$this->getGlobal('errors')) {
            $this->addGlobal('errors', new ViewErrorBag());
        }

        Yoyo::getViewProvider()
            ->getProviderInstance()
            ->composer('*', function ($view) {
                $sections = [];

                foreach ($this->sectionPushes as $section => $contents) {
                    $sections[$section] = implode('', $contents);
                }

                $view->with('sections', $sections);
            });

        app()->bind('flute.view.engine', $this->getBlade());

        $this->addNamespace('flute-icons', path('app/Core/Modules/Icons/Views'));

        $this->blade->compiler()->component(IconComponent::class, 'icon');

        $iconFinder = app()->get(IconFinder::class);

        $iconFinder->registerIconDirectory('fa', storage_path('app/icons/fontawesome'));
        $iconFinder->registerIconDirectory('ph', storage_path('app/icons/phosphoricons'));
        $iconFinder->registerIconDirectory('si', storage_path('app/icons/simpleicons'));
        $iconFinder->registerIconDirectory('lu', storage_path('app/icons/lucide'));
        $iconFinder->registerIconDirectory('tb', storage_path('app/icons/tabler'));

        events()->dispatch(new TemplateInitialized($this), TemplateInitialized::NAME);

        $this->setYoyoRoute();
    }

    protected function addTranslateDirective(): void
    {
        $this->blade->directive('t', static fn($expression) => "<?php echo __({$expression}); ?>");
    }
}
