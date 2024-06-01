<?php

namespace Flute\Core\Widgets;

use Flute\Core\Contracts\WidgetInterface;

abstract class AbstractWidget implements WidgetInterface
{
    protected int $reloadTime = 0;
    protected string $image = '';
    protected array $widgetAssets = [];
    protected bool $lazyLoad = false;

    /**
     * Returns the widget's content.
     * 
     * @param array $settingValues
     *
     * @return string (BladeOne rendered result) #TODO.
     */
    abstract public function render(array $settingValues = []): string;

    /**
     * The array of settings and default values for the widget
     * 
     * @var array
     */
    public array $settings = [];

    /**
     * Placeholder for async widget.
     * You can customize it by overwriting this method.
     *
     * @param array $settingValues
     * 
     * @return string
     */
    public function placeholder( array $settingValues = [] ): string
    {
        return '';
    }

    /**
     * Called when widget will be removed from the CMS.
     * 
     * @return void
     */
    public function unregister(): void
    {
        return;
    }

    /**
     * Called when widget will be added in the CMS.
     * 
     * @return void
     */
    public function init(): void
    {
        return;
    }

    /**
     * Get widget settings
     * 
     * @return array
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * Set widget settings
     * 
     * @param array $settings
     * @return void
     */
    public function setSettings(array $settings): void
    {
        $this->settings = $settings;
    }

    /**
     * is lazy loaded
     * 
     * @return bool
     */
    public function isLazyLoad(): bool
    {
        return $this->lazyLoad;
    }

    /**
     * Set lazy loaded
     * 
     * @param bool $lazyLoad
     * 
     * @return void
     */
    public function setLazyLoad(bool $lazyLoad): void
    {
        $this->lazyLoad = $lazyLoad;
    }

    /**
     * Force widget render
     * 
     * @param array $settingValues
     * 
     * @return string
     */
    public function forceRender( array $settingValues = [] ): string
    {
        return $this->render($settingValues);
    }

    /**
     * ONLY FOR ASYNC WIDGETS
     * 
     * Should widget params be encrypted before sending them to loader
     * Turning encryption off can help with making custom reloads from javascript, but makes widget params publicly accessible.
     *
     * @return bool
     */
    public function encryptParams() : bool 
    {
        return true;
    }

    /**
     * Get widget image preview
     * 
     * @return string
     */
    public function getImage() : string
    {
        return $this->image;
    }

    /**
     * set widget image preview
     * 
     * @param string $image
     * 
     * @return void
     */
    public function setImage(string $image): void
    {
        $this->image = $image;
    }

    /**
     * Get widget reload time
     * 
     * @return int
     */
    public function getReloadTime(): int
    {
        return $this->reloadTime;
    }

    /**
     * Set widget reload time in seconds
     * 
     * @param int $reloadTime
     * 
     * @return void
     */
    public function setReloadTime(int $reloadTime): void
    {
        $this->reloadTime = $reloadTime;
    }

    /**
     * Get widget assets
     * 
     * @return array
     */
    public function getAssets(): array
    {
        return $this->widgetAssets;
    }
    
    /**
     * Set widget assets
     * 
     * @param array $assets
     *
     * @return void
     */
    public function setAssets(array $assets): void
    {
        $this->widgetAssets = $assets;
    }

    public function getDefaultSettings(): array
    {
        return [];
    }
}
