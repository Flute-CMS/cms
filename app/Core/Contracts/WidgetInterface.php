<?php

namespace Flute\Core\Contracts;

interface WidgetInterface
{
    /**
     * Placeholder for async widget.
     * You can customize it by overwriting this method.
     *
     * @param array $settingValues
     *
     * @return string
     */
    public function placeholder( array $settingValues = [] );

    /**
     * ONLY FOR ASYNC WIDGETS
     * 
     * Should widget params be encrypted before sending them to loader
     * Turning encryption off can help with making custom reloads from javascript, but makes widget params publicly accessible.
     *
     * @return bool
     */
    public function encryptParams() : bool;

    /**
     * Returns the widget's content.
     * 
     * @param array $settingValues
     *
     * @return string (BladeOne rendered result) #TODO.
     */
    public function render( array $settingValues = [] ) : string;

    /**
     * Force render the widget.
     * 
     * @param array $settingValues
     * 
     * @return mixed
     */
    public function forceRender( array $settingValues = [] );

    /**
     * Called when widget will be removed from the CMS.
     * 
     * @return void
     */
    public function unregister(): void;

    /**
     * Called when widget will be added in the CMS. (Every time, not only
     * when the widget is added in database)
     * 
     * @return void
     */
    public function init(): void;

    /**
     * Get widget settings
     * 
     * @return array
     */
    public function getSettings(): array;

    /**
     * Get default widget settings
     * 
     * @return array
     */
    public function getDefaultSettings(): array;

    /**
     * Set widget settings
     * 
     * @param array $settings
     * @return void
     */
    public function setSettings(array $settings): void;

    /**
     * Get widget name
     * 
     * @return string
     */
    public function getName(): string;

    /**
     * Is the widget lazy? I'm yes.
     * 
     * @return bool
     */
    public function isLazyLoad(): bool;

    /**
     * Set lazyload state
     * 
     * @param bool $lazyLoad
     * 
     * @return void
     */
    public function setLazyLoad(bool $lazyLoad): void;

    /**
     * Get widget image preview
     * 
     * @return string
     */
    public function getImage(): string;

    /**
     * set widget image preview
     * 
     * @param string $image
     * 
     * @return void
     */
    public function setImage(string $image): void;

    /**
     * Set reload time (in seconds)
     * 
     * @param int $reloadTime
     * 
     * @return void
     */
    public function setReloadTime(int $reloadTime): void;
    
    /**
     * Get reload time (in seconds)
     * 
     * @return int
     */
    public function getReloadTime(): int;

    /**
     * Get widget assets
     * 
     * @return array
     */
    public function getAssets(): array;

    /**
     * Set widget assets
     * 
     * @param array $assets
     * 
     * @return void
     */
    public function setAssets(array $assets): void;
}