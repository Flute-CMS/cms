<?php

namespace Flute\Admin\Packages\Theme\Screens;

use Flute\Admin\Platform\Actions\DropDown;
use Flute\Admin\Platform\Actions\DropDownItem;
use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\TD;
use Flute\Admin\Platform\Fields\TextArea;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Repository;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Database\Entities\Theme;
use Flute\Core\Theme\ThemeActions;
use Flute\Core\Theme\ThemeManager;

class ThemeScreen extends Screen
{
    public ?string $name = 'admin-theme.title.themes';
    public ?string $description = 'admin-theme.title.description';
    public ?string $permission = 'admin.themes';

    public $themes;
    public $key;

    protected ThemeManager $themeManager;
    protected ThemeActions $themeActions;

    public function mount() : void
    {
        $this->themeManager = app(ThemeManager::class);
        $this->themeActions = app(ThemeActions::class);

        breadcrumb()
            ->add(__('def.admin_panel'), url('/admin'))
            ->add(__('admin-theme.title.themes'));

        $this->loadThemes();
    }

    protected function loadThemes(bool $refresh = false) : void
    {
        if ($refresh) {
            $this->themeManager->reInitThemes();
        }

        $this->themes = $this->themeManager->getAllThemes();
    }

    public function layout() : array
    {
        return [
            LayoutFactory::table('themes', [
                TD::make('name', __('admin-theme.table.name'))
                    ->render(function (Theme $theme) {
                        return view('admin-theme::cells.name', compact('theme'));
                    })
                    ->minWidth('200px'),

                TD::make('version', __('admin-theme.table.version'))
                    ->render(fn(Theme $theme) => view('admin-theme::cells.version', compact('theme')))
                    ->minWidth('150px'),

                TD::make('status', __('admin-theme.table.status'))
                    ->render(function (Theme $theme) {
                        switch ($theme->status) {
                            case ThemeManager::ACTIVE:
                                return '<span class="badge success">' . __('admin-theme.status.active') . '</span>';
                            case ThemeManager::DISABLED:
                                return '<span class="badge warning">' . __('admin-theme.status.inactive') . '</span>';
                            case ThemeManager::NOTINSTALLED:
                                return '<span class="badge error">' . __('admin-theme.status.not_installed') . '</span>';
                            default:
                                return '<span class="badge dark">' . __('admin-theme.status.unknown') . '</span>';
                        }
                    })
                    ->minWidth('100px'),

                TD::make('actions', __('admin-theme.table.actions'))
                    ->width('250px')
                    ->alignCenter()
                    ->render(function (Theme $theme) {
                        $actions = [];

                        if ($theme->status === ThemeManager::NOTINSTALLED) {
                            $actions[] = DropDownItem::make(__('admin-theme.buttons.install'))
                                ->method('installTheme', ['key' => $theme->key])
                                ->icon('ph.bold.download-simple-bold')
                                ->confirm(__('admin-theme.confirms.install'), 'info')
                                ->type(Color::OUTLINE_SUCCESS)
                                ->size('small')
                                ->fullWidth();
                        } elseif ($theme->status === ThemeManager::DISABLED) {
                            $actions[] = DropDownItem::make(__('admin-theme.buttons.enable'))
                                ->method('activateTheme', ['key' => $theme->key])
                                ->icon('ph.bold.play-bold')
                                ->type(Color::OUTLINE_SUCCESS)
                                ->size('small')
                                ->fullWidth();
                        }
                        //  elseif ($theme->status === ThemeManager::ACTIVE) {
                        //     $actions[] = DropDownItem::make(__('admin-theme.buttons.disable'))
                        //         ->method('disableTheme', ['key' => $theme->key])
                        //         ->icon('ph.bold.pause-bold')
                        //         ->type(Color::OUTLINE_WARNING)
                        //         ->size('small')
                        //         ->fullWidth();
                        // }

                        if($theme->key !== 'standard') {
                            $actions[] = DropDownItem::make(__('admin-theme.buttons.delete'))
                                ->confirm(__('admin-theme.confirms.delete'))
                                ->method('uninstallTheme', ['key' => $theme->key])
                                ->icon('ph.bold.trash-bold')
                                ->type(Color::OUTLINE_DANGER)
                                ->size('small')
                                ->fullWidth();
                        }

                        $actions[] = DropDownItem::make(__('admin-theme.buttons.details'))
                            ->modal('themeDetailsModal', ['key' => $theme->key])
                            ->icon('ph.bold.info-bold')
                            ->type(Color::OUTLINE_PRIMARY)
                            ->size('small')
                            ->fullWidth();

                        return DropDown::make()
                            ->icon('ph.regular.dots-three-outline-vertical')
                            ->list($actions);
                    }),
            ])
                ->searchable(['key', 'name'])
                ->commands([
                    Button::make(__('admin-theme.buttons.refresh'))
                        ->icon('ph.regular.arrows-counter-clockwise')
                        ->type(Color::OUTLINE_PRIMARY)
                        ->size('small')
                        ->method('refreshThemes')
                ])
        ];
    }

    public function themeDetailsModal(Repository $parameters)
    {
        $theme = $this->themeManager->getTheme($parameters->get('key'));
        if (!$theme) {
            $this->flashMessage(__('admin-theme.messages.not_found'), 'error');
            return;
        }

        $themeData = $this->themeManager->getThemeData($theme->key);

        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                Input::make('name')
                    ->type('text')
                    ->value($theme->name)
                    ->readOnly()
            )
                ->label(__('admin-theme.fields.name.label')),

            LayoutFactory::field(
                Input::make('version')
                    ->type('text')
                    ->value($theme->version)
                    ->readOnly()
            )
                ->label(__('admin-theme.fields.version.label')),

            LayoutFactory::field(
                TextArea::make('description')
                    ->value($theme->description)
                    ->readOnly(true)
            )
                ->label(__('admin-theme.fields.description.label')),

            LayoutFactory::field(
                Input::make('author')
                    ->type('text')
                    ->value($theme->author)
                    ->readOnly()
            )
                ->label(__('admin-theme.fields.author.label')),

            $themeData && isset($themeData['url']) ? LayoutFactory::field(
                Input::make('url')
                    ->type('url')
                    ->value($themeData['url'])
                    ->readOnly()
            )
                ->label(__('admin-theme.fields.url.label'))
            : null,
        ])
            ->title(__('admin-theme.modals.details.title', ['name' => $theme->name]))
            ->withoutApplyButton()
            ->right();
    }

    public function refreshThemes()
    {
        $this->loadThemes(true);
        $this->flashMessage(__('admin-theme.messages.refresh_success'), 'success');
    }

    public function installTheme()
    {
        try {
            $this->themeActions->installTheme($this->key);
            $this->flashMessage(__('admin-theme.messages.install_success', ['name' => $this->key]), 'success');
        } catch (\Exception $e) {
            $this->flashMessage(__('admin-theme.messages.install_error', ['message' => $e->getMessage()]), 'error');
        }

        $this->loadThemes(true);
    }

    public function activateTheme()
    {
        try {
            $this->themeActions->activateTheme($this->key);
            $this->flashMessage(__('admin-theme.messages.enable_success', ['name' => $this->key]), 'success');
        } catch (\Exception $e) {
            $this->flashMessage(__('admin-theme.messages.enable_error', ['message' => $e->getMessage()]), 'error');
        }

        $this->loadThemes(true);
    }

    public function disableTheme()
    {
        try {
            $this->themeActions->disableTheme($this->key);
            $this->flashMessage(__('admin-theme.messages.disable_success', ['name' => $this->key]), 'success');
        } catch (\Exception $e) {
            $this->flashMessage(__('admin-theme.messages.disable_error', ['message' => $e->getMessage()]), 'error');
        }

        $this->loadThemes(true);
    }

    public function uninstallTheme()
    {
        try {
            $this->themeActions->uninstallTheme($this->key);
            $this->flashMessage(__('admin-theme.messages.delete_success', ['name' => $this->key]), 'success');
        } catch (\Exception $e) {
            $this->flashMessage(__('admin-theme.messages.delete_error', ['message' => $e->getMessage()]), 'error');
        }

        $this->loadThemes(true);
    }
} 