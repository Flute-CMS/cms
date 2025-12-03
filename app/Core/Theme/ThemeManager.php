<?php

namespace Flute\Core\Theme;

use Flute\Core\Database\Entities\Theme;
use Flute\Core\Database\Entities\ThemeSettings;
use Flute\Core\Theme\Events\ThemeChangedEvent;
use Flute\Core\Theme\Events\ThemesInitialized;
use Illuminate\Support\Collection;
use RuntimeException;

class ThemeManager
{
    // Constants for theme statuses
    public const ACTIVE = 'active';

    public const DISABLED = 'disabled';

    public const NOTINSTALLED = 'notinstalled';

    public const DEFAULT_THEME = 'standard';

    protected const CACHE_TIME = 60 * 60; // Cache duration in seconds (1 hour)

    protected const C_KEY_GET = "flute.themes.get";

    public bool $isSafeLoading = false;

    public Collection $installedThemes;

    public Collection $notInstalledThemes;

    public Collection $disabledThemes;

    protected Collection $themes;

    protected string $themesPath;

    protected bool $performance;

    protected array $allThemes = [];

    protected array $themesData = [];

    protected array $allThemesKeys = [];

    protected array $colors = [];

    protected ?string $currentTheme = null;

    protected bool $initialized = false;

    protected bool $isInitializing = false;

    protected bool $colorsInitialized = false;

    /**
     * Constructor for ThemeManager.
     */
    public function __construct()
    {
        $this->themesPath = path('app/Themes');
        $this->performance = (bool) (is_performance());

        $this->themes = collect();
        $this->disabledThemes = collect();
        $this->notInstalledThemes = collect();
        $this->installedThemes = collect();
    }

    /**
     * Initialize the ThemeManager.
     */
    public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->isInitializing = true;

        $this->syncThemesWithDatabase();

        events()->dispatch(new ThemesInitialized($this), ThemesInitialized::NAME);

        $this->initialized = true;

        $this->isInitializing = false;
    }

    /**
     * Get the settings for a specific theme.
     *
     * @throws RuntimeException
     */
    public function getThemeSettings(string $themeName): array
    {
        $this->initialize();

        return $this->getThemeData($themeName)['settings'] ?? [];
    }

    /**
     * Get the theme information for the current theme.
     *
     * @throws RuntimeException
     */
    public function getThemeInfo(): Theme
    {
        $this->initialize();

        return $this->getTheme($this->getCurrentTheme());
    }

    /**
     * Get the current active theme.
     */
    public function getCurrentTheme(): string
    {
        $this->initialize();

        if (!isset($this->currentTheme)) {
            $this->fallbackToDefaultTheme();
        }

        return $this->currentTheme;
    }

    /**
     * Get a list of installed themes.
     */
    public function getInstalledThemes(): Collection
    {
        $this->initialize();

        return $this->installedThemes;
    }

    /**
     * Get all themes from the database.
     */
    public function getAllThemes(): array
    {
        if (empty($this->allThemes)) {
            $this->allThemes = Theme::query()->load('settings')->fetchAll();
        }

        return $this->allThemes;
    }

    /**
     * Check if a theme is active and installed.
     *
     * @throws RuntimeException
     */
    public function checkTheme(string $themeName): void
    {
        $theme = $this->getTheme($themeName);
        if ($theme->status !== self::ACTIVE) {
            throw new RuntimeException("The theme '{$themeName}' is not installed or not active.");
        }
    }

    /**
     * Set the current theme.
     */
    public function setTheme(string $themeKey): void
    {
        $this->currentTheme = $themeKey;

        if (!$this->isInitializing) {
            events()->dispatch(new ThemeChangedEvent($themeKey));
        }
    }

    /**
     * Get theme data from theme.json for a given theme.
     */
    public function getThemeData(string $themeName): ?array
    {
        if (isset($this->themesData[$themeName])) {
            return $this->themesData[$themeName];
        }

        return $this->themesData[$themeName] = $this->loadThemeJson($themeName);
    }

    /**
     * Load all theme.json files for all themes.
     */
    public function loadAllThemesJson(): void
    {
        $themeDirs = array_filter(glob("{$this->themesPath}/*", GLOB_ONLYDIR), static fn ($dir) => basename($dir) !== '.disabled');

        foreach ($themeDirs as $dir) {
            $themeName = basename($dir);
            $this->getThemeData($themeName);
        }
    }

    /**
     * Load theme.json for a specific theme.
     */
    public function loadThemeJson(string $themeName): ?array
    {
        $themeJsonPath = "{$this->themesPath}/{$themeName}/theme.json";

        if (file_exists($themeJsonPath) && is_readable($themeJsonPath)) {
            $themeData = json_decode(file_get_contents($themeJsonPath), true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $this->themesData[$themeName] = $themeData;

                return $themeData;
            }
            logs('templates')->error("Invalid JSON in theme.json for theme '{$themeName}': " . json_last_error_msg());

        } else {
            logs('templates')->error("theme.json not found or not readable for theme '{$themeName}'.");
        }

        return null;
    }

    /**
     * Get a theme by its name.
     *
     * @throws RuntimeException
     */
    public function getTheme(string $themeName): Theme
    {
        if (isset($this->allThemesKeys[$themeName])) {
            return $this->allThemesKeys[$themeName];
        }

        $theme = $this->performance
            ? cache()->callback("flute.themes.{$themeName}", static fn () => Theme::query()->load('settings')->where(['key' => $themeName])->fetchOne(), self::CACHE_TIME)
            : Theme::query()->load('settings')->where(['key' => $themeName])->fetchOne();

        if (!$theme) {
            throw new RuntimeException("The theme '{$themeName}' does not exist.");
        }

        $this->allThemesKeys[$themeName] = $theme;

        return $theme;
    }

    /**
     * Re-initialize themes data.
     */
    public function reInitThemes(): void
    {
        $this->themesData = [];
        $this->loadAllThemesJson();
    }

    /**
     * Fallback to the default theme if the current theme is not set or invalid.
     */
    public function fallbackToDefaultTheme(): void
    {
        if (!isset($this->currentTheme) || $this->currentTheme !== self::DEFAULT_THEME) {
            logs('templates')->warning('The theme was switched to "standard" to prevent interface errors. You need to change the theme manually!');

            if (isset($this->themesData[self::DEFAULT_THEME])) {
                $this->setTheme(self::DEFAULT_THEME);
                $this->isSafeLoading = true;
            } else {
                throw new RuntimeException("Default theme '" . self::DEFAULT_THEME . "' not found.");
            }
        }
    }

    public function loadColors(bool $force = false): void
    {
        if ($this->colorsInitialized && !$force) {
            return;
        }

        $colorsPath = "{$this->themesPath}/{$this->currentTheme}/colors.json";

        if (file_exists($colorsPath) && is_readable($colorsPath)) {
            $colorsData = json_decode(file_get_contents($colorsPath), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->colors = $colorsData;
            }
        }

        $this->colorsInitialized = true;
    }

    public function getColors(?string $mode = null): array
    {
        $this->loadColors();

        return $mode ? $this->colors[$mode] ?? [] : $this->colors;
    }

    /**
     * Sync themes with the database.
     */
    protected function syncThemesWithDatabase(): void
    {
        $this->loadAllThemesJson();

        // Initialize theme collections
        $this->installedThemes = collect();
        $this->disabledThemes = collect();
        $this->notInstalledThemes = collect();

        $themesInDB = is_installed()
            ? $this->getAssocThemes()
            : [];

        foreach ($this->themesData as $themeName => $themeData) {
            // Prevent installer cycle bugs.
            if (!is_installed()) {
                $this->setTheme($themeName);

                break;
            }

            if (!array_key_exists($themeName, $themesInDB)) {
                // Create a new Theme entity
                $newTheme = new Theme();
                $newTheme->name = $themeData['name'] ?? $themeName;
                $newTheme->key = $themeName;
                $newTheme->version = $themeData['version'] ?? "1.0";
                $newTheme->author = $themeData['author'] ?? "";
                $newTheme->description = htmlspecialchars($themeData['description'] ?? "");

                // Set the theme status
                $newTheme->status = ($newTheme->key === self::DEFAULT_THEME) ? self::ACTIVE : self::NOTINSTALLED;

                // Add settings from theme.json
                if (isset($themeData['settings']) && is_array($themeData['settings'])) {
                    foreach ($themeData['settings'] as $key => $value) {
                        $setting = new ThemeSettings();
                        $setting->key = $key;
                        $setting->name = $value['name'] ?? "";
                        $setting->description = $value['description'] ?? "";
                        $setting->value = $value['value'] ?? "";

                        $newTheme->addSetting($setting);
                    }
                }

                transaction($newTheme)->run();

                $themesInDB[$themeName] = $newTheme;
            }

            $themeInDB = $themesInDB[$themeName];

            // Add theme to collections based on status
            switch ($themeInDB->status) {
                case self::ACTIVE:
                    $this->installedThemes->put($themeName, $themeInDB);
                    if (!isset($this->currentTheme)) {
                        $this->setTheme($themeInDB->key);
                    }

                    break;
                case self::DISABLED:
                    $this->disabledThemes->put($themeName, $themeInDB);

                    break;
                case self::NOTINSTALLED:
                    $this->notInstalledThemes->put($themeName, $themeInDB);

                    break;
            }

            $this->themes->put($themeName, $themeInDB);
        }

        // Set current theme if not already set
        if (!isset($this->currentTheme)) {
            $this->fallbackToDefaultTheme();
        }
    }

    /**
     * Get an associative array of themes from the database.
     */
    protected function getAssocThemes(): array
    {
        $themes = $this->performance
            ? cache()->callback('flute.themes.assoc', static fn () => Theme::query()->load('settings')->fetchAll(), self::CACHE_TIME)
            : Theme::query()->load('settings')->fetchAll();

        return array_reduce($themes, static function ($carry, Theme $theme) {
            $carry[$theme->key] = $theme;

            return $carry;
        }, []);
    }
}
