<?php

namespace Flute\Core\Template;

use Flute\Core\App;
use Jenssegers\Blade\Blade;

abstract class AbstractTemplateInstance
{
    /**
     * @var App
     */
    protected App $app;

    /**
     * @var Blade
     */
    protected Blade $blade;
    protected TemplateAssets $templateAssets;
    protected string $cachePath;
    protected string $theme;
    protected string $viewsPath;
}