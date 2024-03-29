<?php

namespace Flute\Core\Template;

use Flute\Core\Template\EditedBladeOne as BladeOne;
use Flute\Core\App;

abstract class AbstractTemplateInstance
{
    /**
     * @var App
     */
    protected App $app;

    /**
     * @var BladeOne
     */
    protected BladeOne $blade;
    protected TemplateAssets $templateAssets;
    protected TemplateVariables $templateVariables;

    protected string $cachePath;
    protected string $theme;
    protected string $viewsPath;
}