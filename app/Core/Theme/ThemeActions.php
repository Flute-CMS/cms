<?php

namespace Flute\Core\Theme;

use Exception;
use Flute\Core\Database\Entities\Theme;
use Flute\Core\Theme\Events\ThemeActivated;
use Flute\Core\Theme\Events\ThemeColorsUpdated;
use Flute\Core\Theme\Events\ThemeInstalled;
use Flute\Core\Theme\Events\ThemeUninstalled;
use Throwable;

class ThemeActions
{
    protected ThemeManager $themeManager;

    /**
     * Constructor method
     * Initializes the theme actions with the theme manager
     */
    public function __construct(ThemeManager $themeManager)
    {
        $this->themeManager = $themeManager;
    }

    /**
     * Update the status of a theme.
     *
     * @param string $themeName
     * @param string $status
     * @throws Throwable
     */
    protected function updateThemeStatus(string $themeName, string $status) : void
    {
        if ($status === ThemeManager::ACTIVE) {
            $themePast = $this->themeManager->getTheme($this->themeManager->getCurrentTheme());
            $themePast->status = ThemeManager::DISABLED;
            transaction($themePast)->run();
        }

        $theme = $this->themeManager->getTheme($themeName);
        $theme->status = $status;
        transaction($theme)->run();
    }

    /**
     * Update the colors of a theme.
     *
     * @param string $themeName The name of the theme to update
     * @param array  $newColors An associative array of colors to update
     * @param string $theme light/dark
     *
     * @throws Exception|Throwable if the theme is not installed or updating fails
     */
    public function updateThemeColors(string $themeName, array $newColors, string $theme) : void
    {
        $this->validateThemeStatus($themeName);

        $colorsPath = BASE_PATH . 'app/Themes/' . $themeName . '/colors.json';

        if (file_exists($colorsPath) && is_readable($colorsPath)) {
            $existingColors = json_decode(file_get_contents($colorsPath), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Invalid JSON in colors.json for theme '{$themeName}': " . json_last_error_msg());
            }
        } else {
            $existingColors = [];
        }

        $updatedColors = array_merge($existingColors, [$theme => $newColors]);

        $jsonData = json_encode($updatedColors, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($jsonData === false) {
            throw new Exception("Failed to encode colors to JSON: " . json_last_error_msg());
        }

        $tempColorsPath = $colorsPath . '.tmp';

        if (file_put_contents($tempColorsPath, $jsonData) === false) {
            throw new Exception("Failed to write to temporary colors file for theme '{$themeName}'.");
        }

        if (!rename($tempColorsPath, $colorsPath)) {
            fs()->remove($tempColorsPath);
            throw new Exception("Failed to update colors.json for theme '{$themeName}'.");
        }

        if ($this->themeManager->getCurrentTheme() === $themeName) {
            $this->themeManager->loadColors(true);
        }

        $event = new ThemeColorsUpdated($themeName, $updatedColors);
        events()->dispatch($event, ThemeColorsUpdated::NAME);

        if ($event->isPropagationStopped()) {
            return;
        }
    }

    /**
     * Validate the status of a theme.
     * If the theme is not installed or disabled, throw an exception.
     *
     * @param string $themeName
     *
     * @throws Exception if the theme is not installed or not activated
     */
    private function validateThemeStatus(string $themeName) : void
    {
        $theme = Theme::findOne(['key' => $themeName]);

        if ($theme->status === ThemeManager::NOTINSTALLED) {
            throw new Exception(sprintf("Theme %s is not installed.", $themeName));
        }
    }

    /**
     * Activate a theme.
     * If the theme is already active, throw an exception.
     * If there is a current theme, disable it.
     * Then activate the new theme and update its status.
     *
     * @param string $themeName
     *
     * @throws Exception|Throwable if the theme is already active
     */
    public function activateTheme(string $themeName) : void
    {
        // He throws Exception if not exists
        $this->themeManager->getTheme($themeName);

        $this->validateThemeStatus($themeName);

        if (!$this->themeManager->isSafeLoading && $this->themeManager->getCurrentTheme() === $themeName) {
            throw new Exception("The theme '{$themeName}' is already active.");
        }

        foreach ($this->themeManager->getInstalledThemes() as $theme) {
            $this->updateThemeStatus($theme->key, ThemeManager::DISABLED);
        }

        $event = new ThemeActivated($themeName);
        events()->dispatch($event, ThemeActivated::NAME);

        if ($event->isPropagationStopped()) {
            return;
        }

        $this->updateThemeStatus($themeName, ThemeManager::ACTIVE);
    }

    /**
     * Install a theme.
     * Then activate the new theme and update its status.
     * Add the theme to the list of installed themes.
     *
     * @param string $themeName
     */
    public function installTheme(string $themeName) : void
    {
        $event = new ThemeInstalled($themeName);
        events()->dispatch($event, ThemeInstalled::NAME);

        if ($event->isPropagationStopped()) {
            return;
        }

        $this->updateThemeStatus($themeName, ThemeManager::ACTIVE);
        $this->themeManager->installedThemes[] = $themeName;
    }

    /**
     * Disable a theme.
     * Then update its status.
     *
     * @param string $themeName
     */
    public function disableTheme(string $themeName) : void
    {
        // He throws Exception if not exists
        $this->themeManager->getTheme($themeName);

        $this->updateThemeStatus($themeName, ThemeManager::DISABLED);
    }

    /**
     * Uninstall a theme.
     * Then update its status and remove it from the list of installed themes.
     *
     * @param string $themeName
     */
    public function uninstallTheme(string $themeName) : void
    {
        // He throws Exception if not exists
        $this->themeManager->getTheme($themeName);

        if ($this->isLastActiveTheme($themeName)) {
            throw new Exception("Cannot uninstall the last active theme.");
        }

        $event = new ThemeUninstalled($themeName);
        events()->dispatch($event, ThemeUninstalled::NAME);

        if ($event->isPropagationStopped()) {
            return;
        }

        $this->themeManager->installedThemes = $this->themeManager->installedThemes->filter(function ($theme) use ($themeName) {
            return $theme !== $themeName;
        });

        if ($this->themeManager->getCurrentTheme() === $themeName) {
            $this->themeManager->fallbackToDefaultTheme();
        }

        $theme = $this->themeManager->getTheme($themeName);
        transaction($theme, 'delete')->run();

        fs()->remove(BASE_PATH . 'app/Themes/' . $themeName);
    }

    private function isLastActiveTheme(string $themeName) : bool
    {
        $activeThemes = $this->themeManager->getInstalledThemes();
        return $activeThemes->count() === 1 && in_array($themeName, $activeThemes->toArray());
    }
}
