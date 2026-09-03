<?php

namespace Flute\Admin\Packages\MainSettings\Screens;

use Flute\Admin\Packages\MainSettings\Layouts\DatabaseSettingsLayout;
use Flute\Admin\Packages\MainSettings\Screens\Concerns\HasAdvancedTab;
use Flute\Admin\Packages\MainSettings\Screens\Concerns\HasCacheActions;
use Flute\Admin\Packages\MainSettings\Screens\Concerns\HasCaptchaSettings;
use Flute\Admin\Packages\MainSettings\Screens\Concerns\HasDatabaseModals;
use Flute\Admin\Packages\MainSettings\Screens\Concerns\HasDatabaseTab;
use Flute\Admin\Packages\MainSettings\Screens\Concerns\HasImageUploads;
use Flute\Admin\Packages\MainSettings\Screens\Concerns\HasLocalizationTab;
use Flute\Admin\Packages\MainSettings\Screens\Concerns\HasMailTab;
use Flute\Admin\Packages\MainSettings\Screens\Concerns\HasMainSettingsTab;
use Flute\Admin\Packages\MainSettings\Screens\Concerns\HasSiteTab;
use Flute\Admin\Packages\MainSettings\Screens\Concerns\HasUsersTab;
use Flute\Admin\Packages\MainSettings\Services\MainSettingsPackageService;
use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Fields\Tab;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Support\FluteStr;
use Throwable;

class MainSettingsPackageScreen extends Screen
{
    use HasAdvancedTab;
    use HasCacheActions;
    use HasCaptchaSettings;
    use HasDatabaseModals;
    use HasDatabaseTab;
    use HasImageUploads;
    use HasLocalizationTab;
    use HasMailTab;
    use HasMainSettingsTab;
    use HasSiteTab;
    use HasUsersTab;

    public $databaseConnections;

    public $logo;

    public $logo_light;

    public $bg_image;

    public $bg_image_light;

    public $default_avatar;

    public $default_banner;

    public $favicon;

    public $social_image;

    public $profileTabs;

    protected string $name = 'admin-main-settings.labels.main_settings';

    protected ?string $description = 'admin-main-settings.labels.main_settings_description';

    protected $permission = 'admin.system';

    protected MainSettingsPackageService $configService;

    public function mount(): void
    {
        breadcrumb()->add(__('admin-main-settings.breadcrumbs.admin_panel'), url('/admin'))->add(__(
            'admin-main-settings.tabs.main_settings',
        ));

        $this->configService = app(MainSettingsPackageService::class);
        $this->databaseConnections = $this->configService->initDatabases();
        $this->loadProfileTabs();
    }

    public function commandBar(): array
    {
        return [
            Button::make(__('admin-main-settings.buttons.clear_cache'))
                ->icon('ph.regular.cloud')
                ->method('clearCache')
                ->type(Color::OUTLINE_WARNING),

            Button::make(__('admin-main-settings.buttons.save'))->method('save'),
        ];
    }

    public function layout(): array
    {
        return [
            LayoutFactory::tabs([
                Tab::make(__('admin-main-settings.tabs.main_settings'))
                    ->slug('general')
                    ->icon('ph.bold.gear-bold')
                    ->layouts([
                        $this->mainSettingsLayout(),
                    ]),
                Tab::make(__('admin-main-settings.tabs.databases'))
                    ->slug('databases')
                    ->icon('ph.bold.cloud-bold')
                    ->layouts([
                        DatabaseSettingsLayout::class,
                    ]),
                Tab::make(__('admin-main-settings.tabs.users'))
                    ->slug('users')
                    ->icon('ph.bold.user-circle-bold')
                    ->layouts([
                        $this->usersSettingsLayout(),
                    ]),
                Tab::make(__('admin-main-settings.tabs.site'))
                    ->slug('site')
                    ->icon('ph.bold.browser-bold')
                    ->layouts([
                        $this->siteSettingsLayout(),
                    ]),
                Tab::make(__('admin-main-settings.tabs.mail'))
                    ->slug('mail')
                    ->icon('ph.bold.envelope-bold')
                    ->layouts([
                        $this->mailSettingsLayout(),
                    ]),
                Tab::make(__('admin-main-settings.tabs.localization'))
                    ->slug('localization')
                    ->icon('ph.bold.translate-bold')
                    ->layouts([
                        $this->localizationSettingsLayout(),
                    ]),
                Tab::make(__('admin-main-settings.tabs.advanced'))
                    ->slug('advanced')
                    ->icon('ph.bold.sliders-bold')
                    ->layouts([
                        $this->advancedSettingsLayout(),
                    ]),
            ])->slug('settings')->pills(),
        ];
    }

    public function save()
    {
        $currentTab = request()->input('tab-settings', FluteStr::slug(__('admin-main-settings.tabs.main_settings')));

        $debugBefore = (bool) config('app.debug');
        $devBefore = (bool) config('app.development_mode');
        $performanceBefore = (bool) config('app.is_performance');
        $localeBefore = (string) config('lang.locale');
        $availableBefore = (array) config('lang.available');

        try {
            $save = $this->configService->saveSettings($currentTab, request()->input());

            if ($save) {
                $this->flashMessage(__('admin-main-settings.messages.settings_saved_successfully'));

                $debugAfter = (bool) config('app.debug');
                $devAfter = (bool) config('app.development_mode');
                $performanceAfter = (bool) config('app.is_performance');

                $localeChanged = $localeBefore !== (string) config('lang.locale');
                $availableChanged = $availableBefore !== (array) config('lang.available');

                if (
                    $debugBefore !== $debugAfter
                    || $devBefore !== $devAfter
                    || $performanceBefore !== $performanceAfter
                    || $localeChanged
                    || $availableChanged
                ) {
                    $this->clearCache();
                } else {
                    $this->invalidateSettingsCache();
                }
            }
        } catch (Throwable $e) {
            $this->flashMessage(__('admin-main-settings.messages.settings_save_error') . $e->getMessage(), 'error');
        }
    }
}
