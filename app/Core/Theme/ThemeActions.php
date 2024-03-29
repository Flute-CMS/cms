<?php

namespace Flute\Core\Theme;

use Exception;
use Flute\Core\Database\Entities\Theme;
use Flute\Core\Theme\Events\ThemeActivated;
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
    protected function updateThemeStatus(string $themeName, string $status): void
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
     * Validate the status of a theme.
     * If the theme is not installed or disabled, throw an exception.
     *
     * @param string $themeName
     *
     * @throws Exception if the theme is not installed or not activated
     */
    private function validateThemeStatus(string $themeName): void
    {
        $theme = rep(Theme::class)->findOne(['name' => $themeName]);

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
    public function activateTheme(string $themeName): void
    {
        // He throws Exception if not exists
        $this->themeManager->getTheme($themeName);

        $this->validateThemeStatus($themeName);

        if (!$this->themeManager->isSafeLoading && isset($this->themeManager->currentTheme) && $this->themeManager->currentTheme === $themeName) {
            throw new Exception("The theme '{$themeName}' is already active.");
        }

        if (isset($this->themeManager->currentTheme)) {
            $this->themeManager->themeLoader->disable();
        }

        $themeLoader = $this->themeManager->getThemeLoader($themeName);
        $themeLoader->activate();

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
    public function installTheme(string $themeName): void
    {
        $themeLoader = $this->themeManager->getThemeLoader($themeName);
        $themeLoader->install();

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
    public function disableTheme(string $themeName): void
    {
        // He throws Exception if not exists
        $this->themeManager->getTheme($themeName);

        $themeLoader = $this->themeManager->getThemeLoader($themeName);
        $themeLoader->disable();

        $this->updateThemeStatus($themeName, ThemeManager::DISABLED);
    }

    /**
     * Uninstall a theme.
     * Then update its status and remove it from the list of installed themes.
     *
     * @param string $themeName
     */
    public function uninstallTheme(string $themeName): void
    {
        // He throws Exception if not exists
        $this->themeManager->getTheme($themeName);

        if ($this->isLastActiveTheme($themeName)) {
            throw new Exception("Cannot uninstall the last active theme.");
        }

        $themeLoader = $this->themeManager->getThemeLoader($themeName);

        if (method_exists($themeLoader, "uninstall"))
            $themeLoader->uninstall();

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

    private function isLastActiveTheme(string $themeName): bool
    {
        $activeThemes = $this->themeManager->getInstalledThemes();
        return $activeThemes->count() === 1 && in_array($themeName, $activeThemes->toArray());
    }
}
