<?php

namespace Flute\Core\Template;

use Flute\Core\App;
use Jenssegers\Blade\Blade;

abstract class AbstractTemplateInstance
{
    /**
     */
    protected App $app;

    /**
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
     */
    public function setCacheEnabled(bool $enabled): void
    {
        $this->cacheEnabled = $enabled;
    }

    /**
     * Set maximum cache size.
     */
    public function setMaxCacheSize(int $size): void
    {
        $this->maxCacheSize = $size;
    }

    /**
     * Clear all template caches.
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
