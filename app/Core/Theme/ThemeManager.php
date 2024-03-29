<?php

namespace Flute\Core\Theme;

use Cycle\ORM\Relation\Pivoted\PivotedCollection;
use Cycle\ORM\Select\Repository;
use Flute\Core\App;
use Flute\Core\Contracts\ThemeLoaderInterface;
use Flute\Core\Database\Entities\Theme;
use Flute\Core\Database\Entities\ThemeSettings;
use Flute\Core\Theme\Events\ThemesInitialized;
use Flute\Core\Support\Collection;
use Flute\Core\Template\Template;
use RuntimeException;

class ThemeManager
{
    // Define constants for theme statuses
    public const ACTIVE = 'active';
    public const DISABLED = 'disabled';
    public const NOTINSTALLED = 'notinstalled';

    protected const CACHE_TIME = 24 * 60 * 60;
    protected Collection $themes;
    protected array $themesLoaders;
    protected string $themesPath;
    protected bool $performance;
    public bool $isSafeLoading = false;
    public string $currentTheme;
    protected ThemeFinder $finder;
    public ?ThemeLoaderInterface $themeLoader = null;
    protected Template $template;
    protected ?Repository $repository = null;
    public Collection $installedThemes;
    public Collection $notInstalledThemes;
    public Collection $disabledThemes;

    protected const C_KEY_ALL = "flute.themes.all";
    protected const C_KEY_GET = "flute.themes.get";

    public function __construct(ThemeFinder $finder, Template $template)
    {
        $this->themesPath = path('app/Themes');
        $this->performance = (bool) (app('app.mode') == App::PERFORMANCE_MODE);
        $this->finder = $finder;
        $this->template = $template;
        $this->themesLoaders = $this->getThemeLoaders();

        $this->themes = collect();
        $this->disabledThemes = collect();
        $this->notInstalledThemes = collect();
        $this->installedThemes = collect();

        $this->syncThemesWithDatabase();
        events()->dispatch(new ThemesInitialized($this), ThemesInitialized::NAME);
    }

    public function getThemeSettings(string $themeName): PivotedCollection
    {
        return $this->getTheme($themeName)->getSettings();
    }

    public function getThemeInfo(): Theme
    {
        return $this->getTheme($this->getCurrentTheme());
    }

    public function getCurrentTheme(): string
    {
        if (!isset ($this->currentTheme))
            $this->fallbackToDefaultTheme();

        return $this->currentTheme;
    }

    public function getCurrentThemeLoader(): ?ThemeLoaderInterface
    {
        return $this->themeLoader;
    }

    public function resolveThemeLoader(string $themeName): ThemeLoaderInterface
    {
        if (!isset ($this->themeLoader) || $this->currentTheme !== $themeName) {
            $this->themeLoader = $this->finder->getThemeLoader($themeName);
            $this->currentTheme = $themeName;
        }

        return $this->themeLoader;
    }

    public function getInstalledThemes(): Collection
    {
        return $this->installedThemes;
    }

    public function getAllThemes(): array
    {
        return $this->performance ? cache()->callback(self::C_KEY_ALL, fn() => $this->getRepository()->select()->load('settings')->fetchAll(), self::CACHE_TIME) : (array) $this->getRepository()->select()->load('settings')->fetchAll();
    }

    public function checkTheme(string $themeName): void
    {
        $theme = $this->getTheme($themeName);
        if ($theme->status !== self::ACTIVE) {
            throw new RuntimeException("The theme {$themeName} is not installed or not active");
        }
    }

    public function setTheme(string $themeKey): void
    {
        $this->currentTheme = $themeKey;
    }

    public function setThemeLoader(object $themeLoader): void
    {
        $this->themeLoader = $themeLoader;
    }

    // Database sync methods

    protected function getAssocThemes(): array
    {
        return array_reduce($this->getRepository()->select()->load('settings')->fetchAll(), function ($carry, $theme) {
            $carry[$theme->key] = $theme;
            return $carry;
        }, []);
    }

    /**
     * Syncs the themes with the database.
     * If a theme is found in the theme path but not in the database, it is added to the database.
     * @throws \Throwable
     */
    private function syncThemesWithDatabase(): void
    {
        $themesInDB = is_installed() ? $this->performance ? cache()->callback(self::C_KEY_ALL, fn() => $this->getAssocThemes(), self::CACHE_TIME) : $this->getAssocThemes() : [];

        foreach ($this->themesLoaders as $themeName => $themeLoader) {
            /** @var ThemeLoaderInterface $themeLoader */

            // For preventing installer cycle bugs.
            if (!is_installed() && $themeLoader->getKey() === 'standard') {
                app()->setTheme($themeLoader->getKey());
                $this->template->setThemeLoader($themeLoader);
                $this->themeLoader = $themeLoader;

                break;
            }

            if (!array_key_exists($themeLoader->getKey(), $themesInDB)) {
                $newTheme = new Theme();

                $newTheme->name = $themeLoader->getName();
                $newTheme->key = $themeLoader->getKey();
                $newTheme->version = $themeLoader->getVersion() ?? "1.0";
                $newTheme->author = $themeLoader->getAuthor();
                $newTheme->description = htmlspecialchars($themeLoader->getDescription()) ?? "";

                // Сразу резервируем standard как основной шаблон. И если он был добавлен, то он сразу активный
                $newTheme->status = $newTheme->key === "standard" ? self::ACTIVE : self::NOTINSTALLED;

                foreach ($themeLoader->getSettings() as $key => $value) {
                    $setting = new ThemeSettings;
                    $setting->key = $key;
                    $setting->description = $value['description'] ?? "";
                    $setting->name = $value['name'] ?? "";
                    $setting->value = $value['value'] ?? "";

                    $newTheme->addSetting($setting);
                }

                transaction($newTheme)->run();
            } else {
                // Используем данные о теме из базы данных
                $themeInDB = $themesInDB[$themeLoader->getKey()];

                // Если тема активна, устанавливаем её и загрузчик темы
                if ($themeInDB && $themeInDB->status === self::ACTIVE) {
                    app()->setTheme($themeInDB->key);
                    $this->template->setThemeLoader($themeLoader);
                    $this->themeLoader = $themeLoader;
                    $this->setTheme($themeLoader->getKey());

                    if ($themeInDB->settings)
                        $this->addSettingsVars($themeInDB->settings);
                }
            }
        }
    }

    protected function addSettingsVars($settings)
    {
        foreach( $settings as $setting ) {
            $this->template->addGlobal($setting->key, $setting->value);
        }
    }

    // Helper methods

    /**
     * Get a theme by its name.
     *
     * @param string $themeName
     *
     * @return Theme The theme.
     *
     * @throws RuntimeException if the theme does not exist
     */
    public function getTheme(string $themeName): Theme
    {
        $theme = $this->performance ? cache()->callback("flute.themes.$themeName", fn() => $this->getRepository()->select()->load('settings')->fetchOne(['key' => $themeName]), self::CACHE_TIME) : $this->getRepository()->select()->load('settings')->fetchOne(['key' => $themeName]);
        if (!$theme) {
            throw new RuntimeException("The theme {$themeName} does not exist");
        }
        return $theme;
    }

    public function reInitLoaders(): void
    {
        $this->themesLoaders = $this->getThemeLoaders();
    }

    protected function getRepository(): Repository
    {
        return $this->repository ??= rep(Theme::class);
    }

    protected function getThemeLoaders(): array
    {
        $loaders = $this->performance ? cache()->callback(self::C_KEY_GET, function () {
            return $this->finder->getAllThemeLoaders($this->themesPath);
        }, self::CACHE_TIME) : $this->finder->getAllThemeLoaders($this->themesPath);

        $result = [];

        foreach ($loaders as $theme => $value) {
            if (!class_exists($value)) {
                logs('templates')->error("Theme loader {$value} does not exist");
                continue;
            }

            $result[$theme] = app()->make($value);
        }

        return $result;
    }

    public function getThemeLoader(string $themeName)
    {
        return $this->themesLoaders[$themeName] ?? null;
    }

    public function fallbackToDefaultTheme(): void
    {
        $defaultTheme = 'standard';

        if (!isset ($this->currentTheme) || $this->currentTheme !== $defaultTheme) {
            // (new ThemeActions($this))->activateTheme('standard');

            logs('templates')->warning('Theme was switched to the "standard" for preventing interface error. You need to change theme manually!');

            app()->setTheme($defaultTheme);
            $this->themeLoader = $this->resolveThemeLoader($defaultTheme);
            $this->template->setThemeLoader($this->themeLoader);
            $this->currentTheme = $this->themeLoader->getKey();

            $this->isSafeLoading = true;
        }
    }
}
