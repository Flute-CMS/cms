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

    // Performance optimization properties
    protected array $renderCache = [];
    protected bool $cacheEnabled = true;
    protected int $maxCacheSize = 1000;

    /**
     * Enable or disable caching.
     *
     * @param bool $enabled
     * @return void
     */
    public function setCacheEnabled(bool $enabled): void
    {
        $this->cacheEnabled = $enabled;
    }

    /**
     * Set maximum cache size.
     *
     * @param int $size
     * @return void
     */
    public function setMaxCacheSize(int $size): void
    {
        $this->maxCacheSize = $size;
    }

    /**
     * Clear all template caches.
     *
     * @return void
     */
    public function clearAllCaches(): void
    {
        $this->renderCache = [];

        if (isset($this->templateAssets)) {
            $this->templateAssets->clearCache();
        }
    }

    /**
     * Get memory usage statistics.
     *
     * @return array
     */
    public function getMemoryStats(): array
    {
        return [
            'current_usage' => memory_get_usage(true),
            'peak_usage' => memory_get_peak_usage(true),
            'render_cache_size' => count($this->renderCache),
            'cache_enabled' => $this->cacheEnabled,
        ];
    }
}
