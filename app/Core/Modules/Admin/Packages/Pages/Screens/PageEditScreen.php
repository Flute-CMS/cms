<?php

namespace Flute\Admin\Packages\Pages\Screens;

use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\TextArea;
use Flute\Admin\Platform\Fields\Tab;
use Flute\Admin\Platform\Fields\TD;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Repository;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Database\Entities\Page;
use Flute\Core\Database\Entities\PageBlock;
use Flute\Core\Database\Entities\Permission;
use Flute\Admin\Platform\Actions\DropDown;
use Flute\Admin\Platform\Actions\DropDownItem;
use Flute\Admin\Platform\Fields\CheckBox;

class PageEditScreen extends Screen
{
    public ?string $name = null;
    public ?string $description = null;
    public ?string $permission = 'admin.pages';

    public ?Page $page = null;
    public ?int $pageId = null;
    public $pageBlocks;
    public $permissions;

    public bool $isEditMode = false;

    /**
     * Инициализация экрана при загрузке.
     */
    public function mount() : void
    {
        $this->pageId = (int) request()->input('id');

        if ($this->pageId) {
            $this->initPage();
            $this->isEditMode = true;
        } else {
            $this->name = __('admin-pages.title.create');
            $this->description = __('admin-pages.title.description');
        }

        $this->permissions = Permission::findAll();

        breadcrumb()
            ->add(__('def.admin_panel'), url('/admin'))
            ->add(__('admin-pages.title.list'), url('/admin/pages'))
            ->add($this->pageId ? $this->page->title : __('admin-pages.title.create'));
    }

    protected function initPage() : void
    {
        $this->page = Page::findByPK($this->pageId);

        if (!$this->page) {
            $this->flashMessage(__('admin-pages.messages.page_not_found'), 'error');
            $this->redirectTo('/admin/pages', 300);
            return;
        }

        $this->pageBlocks = $this->page->blocks;
        $this->name = __('admin-pages.title.edit') . ': ' . $this->page->title;
    }

    /**
     * Командная панель с кнопками действий.
     */
    public function commandBar() : array
    {
        $buttons = [
            Button::make(__('admin-pages.buttons.cancel'))
                ->type(Color::OUTLINE_PRIMARY)
                ->redirect('/admin/pages'),
        ];

        if (user()->can('admin.pages')) {
            $buttons[] = Button::make(__('admin-pages.buttons.save'))
                ->type(Color::PRIMARY)
                ->icon('ph.bold.floppy-disk-bold')
                ->method('savePage');
        }

        return $buttons;
    }

    /**
     * Определение макета экрана с использованием вкладок.
     */
    public function layout() : array
    {
        $tabs = [];

        $tabs[] = Tab::make(__('admin-pages.tabs.main'))
            ->icon('ph.bold.gear-bold')
            ->layouts([$this->mainTabLayout()])
            ->active(true);

        if ($this->pageId) {
            $tabs[] = Tab::make(__('admin-pages.tabs.blocks'))
                ->icon('ph.bold.squares-four-bold')
                ->layouts([$this->blocksLayout()])
                ->badge(sizeof($this->pageBlocks ?? []));

            $tabs[] = Tab::make(__('admin-pages.tabs.permissions'))
                ->icon('ph.bold.shield-bold')
                ->layouts([$this->permissionsLayout()]);
        }

        return [
            LayoutFactory::tabs($tabs)
                ->slug('page-edit')
                ->pills(),
        ];
    }

    /**
     * Макет вкладки "Основные".
     */
    private function mainTabLayout()
    {
        $canEditPage = user()->can('admin.pages');

        return $this->pageId ? LayoutFactory::split([
            $this->getMainLayout($canEditPage),
            $this->getActionsLayout($canEditPage)
        ])->ratio('70/30') : $this->getMainLayout($canEditPage);
    }

    private function getMainLayout(bool $canEditPage)
    {
        $fields = [
            LayoutFactory::field(
                Input::make('route')
                    ->type('text')
                    ->value($this->page?->route ?? '')
                    ->disabled(!$canEditPage)
                    ->placeholder(__('admin-pages.fields.route.placeholder'))
            )
                ->label(__('admin-pages.fields.route.label'))
                ->small(__('admin-pages.fields.route.help'))
                ->required(),

            LayoutFactory::field(
                Input::make('title')
                    ->type('text')
                    ->value($this->page?->title ?? '')
                    ->disabled(!$canEditPage)
                    ->placeholder(__('admin-pages.fields.title.placeholder'))
            )
                ->label(__('admin-pages.fields.title.label'))
                ->small(__('admin-pages.fields.title.help'))
                ->required(),

            LayoutFactory::field(
                Textarea::make('description')
                    ->value($this->page?->description ?? '')
                    ->disabled(!$canEditPage)
                    ->placeholder(__('admin-pages.fields.description.placeholder'))
                    ->rows(3)
            )
                ->label(__('admin-pages.fields.description.label'))
                ->small(__('admin-pages.fields.description.help')),

            LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('keywords')
                        ->type('text')
                        ->value($this->page?->keywords ?? '')
                        ->disabled(!$canEditPage)
                        ->placeholder(__('admin-pages.fields.keywords.placeholder'))
                )
                    ->label(__('admin-pages.fields.keywords.label'))
                    ->small(__('admin-pages.fields.keywords.help')),

                LayoutFactory::field(
                    Input::make('robots')
                        ->type('text')
                        ->value($this->page?->robots ?? '')
                        ->disabled(!$canEditPage)
                        ->placeholder(__('admin-pages.fields.robots.placeholder'))
                )
                    ->label(__('admin-pages.fields.robots.label'))
                    ->small(__('admin-pages.fields.robots.help')),
            ]),

            LayoutFactory::field(
                Input::make('og_image')
                    ->type('text')
                    ->value($this->page?->og_image ?? '')
                    ->disabled(!$canEditPage)
                    ->placeholder(__('admin-pages.fields.og_image.placeholder'))
            )
                ->label(__('admin-pages.fields.og_image.label'))
                ->small(__('admin-pages.fields.og_image.help')),
        ];

        return LayoutFactory::block($fields)
            ->title(__('admin-pages.title.main_info'));
    }

    private function getActionsLayout(bool $canEditPage)
    {
        return LayoutFactory::rows([
            Button::make(__('admin-pages.buttons.delete'))
                ->type(Color::OUTLINE_DANGER)
                ->icon('ph.bold.trash-bold')
                ->setVisible($canEditPage && $this->pageId)
                ->method('deletePage')
                ->confirm(__('admin-pages.confirms.delete_page'))
                ->fullWidth(),
        ])
            ->title(__('admin-pages.title.actions'))
            ->description(__('admin-pages.title.actions_description'))
            ->setVisible($this->pageId);
    }

    /**
     * Макет вкладки "Блоки".
     */
    private function blocksLayout()
    {
        return LayoutFactory::table('pageBlocks', [
            TD::make('widget', __('admin-pages.blocks.fields.widget.label'))
                ->render(fn(PageBlock $block) => $block->widget)
                ->width('200px'),

            TD::make('settings', __('admin-pages.blocks.fields.settings.label'))
                ->render(function (PageBlock $block) {
                    $settings = json_decode($block->settings ?? '{}', true);
                    $settingsCount = count($settings);
                    
                    return sprintf(
                        '%d %s',
                        $settingsCount,
                        $settingsCount === 1 ? 'setting' : 'settings'
                    );
                })
                ->width('150px'),

            TD::make('actions', __('admin-pages.buttons.actions'))
                ->render(fn(PageBlock $block) => $this->blockActionsDropdown($block))
                ->width('100px'),
        ])
            ->searchable([
                'widget'
            ])
            ->commands([
                Button::make(__('admin-pages.blocks.add.button'))
                    ->type(Color::OUTLINE_PRIMARY)
                    ->icon('ph.bold.plus-bold')
                    ->modal('addBlockModal')
                    ->fullWidth(),
            ])
            ->setVisible($this->pageId);
    }

    /**
     * Макет вкладки "Разрешения".
     */
    private function permissionsLayout()
    {
        $permissionsCheckboxes = [];
        $pagePermissions = array_map(fn($permission) => $permission->id, $this->page?->permissions ?? []);

        foreach ($this->permissions as $permission) {
            $permissionsCheckboxes[] = LayoutFactory::field(
                CheckBox::make("permissions.{$permission->id}")
                    ->label($permission->name)
                    ->popover(__('permissions.' . $permission->name))
                    ->checked(in_array($permission->id, $pagePermissions))
            );
        }

        return LayoutFactory::block([
            LayoutFactory::split($permissionsCheckboxes),
        ])
            ->title(__('admin-pages.title.permissions'))
            ->setVisible($this->pageId);
    }

    /**
     * Действия над блоком через выпадающее меню.
     */
    private function blockActionsDropdown(PageBlock $block) : string
    {
        return DropDown::make()
            ->icon('ph.regular.dots-three-outline-vertical')
            ->list([
                DropDownItem::make(__('admin-pages.buttons.edit'))
                    ->modal('editBlockModal', ['blockId' => $block->id])
                    ->icon('ph.bold.pencil-bold')
                    ->type(Color::OUTLINE_PRIMARY)
                    ->size('small')
                    ->fullWidth(),

                DropDownItem::make(__('admin-pages.buttons.delete'))
                    ->confirm(__('admin-pages.blocks.delete.confirm'))
                    ->method('deleteBlock', ['blockId' => $block->id])
                    ->icon('ph.bold.trash-bold')
                    ->type(Color::OUTLINE_DANGER)
                    ->size('small')
                    ->fullWidth(),
            ]);
    }

    /**
     * Модальное окно для добавления блока.
     */
    public function addBlockModal(Repository $parameters)
    {
        $fields = [
            LayoutFactory::field(
                Input::make('widget')
                    ->type('text')
                    ->placeholder(__('admin-pages.blocks.fields.widget.placeholder'))
            )
                ->label(__('admin-pages.blocks.fields.widget.label'))
                ->small(__('admin-pages.blocks.fields.widget.help'))
                ->required(),

            LayoutFactory::field(
                Textarea::make('gridstack')
                    ->placeholder(__('admin-pages.blocks.fields.gridstack.placeholder'))
                    ->rows(4)
                    ->value('{}')
            )
                ->label(__('admin-pages.blocks.fields.gridstack.label'))
                ->small(__('admin-pages.blocks.fields.gridstack.help')),

            LayoutFactory::field(
                Textarea::make('settings')
                    ->placeholder(__('admin-pages.blocks.fields.settings.placeholder'))
                    ->rows(6)
                    ->value('{}')
            )
                ->label(__('admin-pages.blocks.fields.settings.label'))
                ->small(__('admin-pages.blocks.fields.settings.help')),
        ];

        return LayoutFactory::modal($parameters, $fields)
            ->title(__('admin-pages.blocks.add.title'))
            ->applyButton(__('admin-pages.buttons.add'))
            ->method('addBlock');
    }

    /**
     * Модальное окно для редактирования блока.
     */
    public function editBlockModal(Repository $parameters)
    {
        $blockId = $parameters->get('blockId');
        $block = PageBlock::findByPK($blockId);

        if (!$block) {
            $this->flashMessage(__('admin-pages.messages.block_not_found'), 'danger');
            return;
        }

        $fields = [
            LayoutFactory::field(
                Input::make('widget')
                    ->type('text')
                    ->value($block->widget)
                    ->placeholder(__('admin-pages.blocks.fields.widget.placeholder'))
            )
                ->label(__('admin-pages.blocks.fields.widget.label'))
                ->small(__('admin-pages.blocks.fields.widget.help'))
                ->required(),

            LayoutFactory::field(
                Textarea::make('gridstack')
                    ->value($block->gridstack)
                    ->placeholder(__('admin-pages.blocks.fields.gridstack.placeholder'))
                    ->rows(4)
            )
                ->label(__('admin-pages.blocks.fields.gridstack.label'))
                ->small(__('admin-pages.blocks.fields.gridstack.help')),

            LayoutFactory::field(
                Textarea::make('settings')
                    ->value($block->settings)
                    ->placeholder(__('admin-pages.blocks.fields.settings.placeholder'))
                    ->rows(6)
            )
                ->label(__('admin-pages.blocks.fields.settings.label'))
                ->small(__('admin-pages.blocks.fields.settings.help')),
        ];

        return LayoutFactory::modal($parameters, $fields)
            ->title(__('admin-pages.blocks.edit.title'))
            ->applyButton(__('admin-pages.buttons.save'))
            ->method('updateBlock');
    }

    /**
     * Сохранение страницы.
     */
    public function savePage()
    {
        $data = request()->input();

        $validation = $this->validate([
            'route' => ['required', 'string', 'max-str-len:255', 'regex:/^\/[a-zA-Z0-9\/_-]*$/'],
            'title' => ['required', 'string', 'max-str-len:255'],
            'description' => ['nullable', 'string', 'max-str-len:1000'],
            'keywords' => ['nullable', 'string', 'max-str-len:500'],
            'robots' => ['nullable', 'string', 'max-str-len:255'],
            'og_image' => ['nullable', 'string', 'max-str-len:500'],
        ], $data);

        if (!$validation) {
            return;
        }

        // Проверяем уникальность маршрута
        $existingPage = Page::findOne(['route' => $data['route']]);
        if ($existingPage && (!$this->page || $existingPage->id !== $this->page->id)) {
            $this->inputError('route', __('admin-pages.messages.route_exists'));
            return;
        }

        try {
            if (!$this->page) {
                $this->page = new Page();
            }

            $this->page->route = $data['route'];
            $this->page->title = $data['title'];
            $this->page->description = $data['description'] ?? null;
            $this->page->keywords = $data['keywords'] ?? null;
            $this->page->robots = $data['robots'] ?? null;
            $this->page->og_image = $data['og_image'] ?? null;

            $this->page->save();

            // Обработка разрешений
            if ($this->pageId && isset($data['permissions'])) {
                $this->page->permissions = [];
                foreach ($data['permissions'] as $permissionId => $value) {
                    if ($value === 'on') {
                        $permission = Permission::findByPK($permissionId);
                        if ($permission) {
                            $this->page->addPermission($permission);
                        }
                    }
                }
                $this->page->save();
            }

            if (!$this->pageId) {
                $this->flashMessage(__('admin-pages.messages.page_created'), 'success');
                $this->redirect('/admin/pages/' . $this->page->id . '/edit');
            } else {
                $this->flashMessage(__('admin-pages.messages.page_updated'), 'success');
            }
        } catch (\Exception $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    /**
     * Добавление блока.
     */
    public function addBlock()
    {
        if (!$this->page) {
            $this->flashMessage(__('admin-pages.messages.save_page_first'), 'error');
            return;
        }

        $data = request()->input();

        $validation = $this->validate([
            'widget' => ['required', 'string', 'max-str-len:255'],
            'gridstack' => ['nullable', 'string'],
            'settings' => ['nullable', 'string'],
        ], $data);

        if (!$validation) {
            return;
        }

        // Проверяем JSON
        if (!empty($data['gridstack'])) {
            $gridstack = json_decode($data['gridstack'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->inputError('gridstack', __('admin-pages.messages.invalid_json'));
                return;
            }
        }

        if (!empty($data['settings'])) {
            $settings = json_decode($data['settings'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->inputError('settings', __('admin-pages.messages.invalid_json'));
                return;
            }
        }

        try {
            $block = new PageBlock();
            $block->widget = $data['widget'];
            $block->gridstack = $data['gridstack'] ?: '{}';
            $block->settings = $data['settings'] ?: '{}';
            $block->page = $this->page;
            $block->save();

            $this->flashMessage(__('admin-pages.messages.block_add_success'), 'success');
            $this->closeModal();
            $this->initPage();
        } catch (\Exception $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    /**
     * Обновление блока.
     */
    public function updateBlock()
    {
        $data = request()->input();
        $blockId = $this->modalParams->get('blockId');
        $block = PageBlock::findByPK($blockId);

        if (!$block) {
            $this->flashMessage(__('admin-pages.messages.block_not_found'), 'error');
            return;
        }

        $validation = $this->validate([
            'widget' => ['required', 'string', 'max-str-len:255'],
            'gridstack' => ['nullable', 'string'],
            'settings' => ['nullable', 'string'],
        ], $data);

        if (!$validation) {
            return;
        }

        // Проверяем JSON
        if (!empty($data['gridstack'])) {
            $gridstack = json_decode($data['gridstack'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->inputError('gridstack', __('admin-pages.messages.invalid_json'));
                return;
            }
        }

        if (!empty($data['settings'])) {
            $settings = json_decode($data['settings'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->inputError('settings', __('admin-pages.messages.invalid_json'));
                return;
            }
        }

        try {
            $block->widget = $data['widget'];
            $block->gridstack = $data['gridstack'] ?: '{}';
            $block->settings = $data['settings'] ?: '{}';
            $block->save();

            $this->initPage();
            $this->flashMessage(__('admin-pages.messages.block_update_success'), 'success');
            $this->closeModal();
        } catch (\Exception $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    /**
     * Удаление блока.
     */
    public function deleteBlock()
    {
        $blockId = request()->input('blockId');

        try {
            $block = PageBlock::findByPK($blockId);
            if ($block) {
                $block->delete();
                $this->flashMessage(__('admin-pages.messages.block_delete_success'), 'success');
                $this->initPage();
            }
        } catch (\Exception $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    /**
     * Удаление страницы.
     */
    public function deletePage()
    {
        if (!user()->can('admin.pages')) {
            $this->flashMessage(__('admin-pages.messages.no_permission.delete'), 'error');
            return;
        }

        try {
            // Удаляем все блоки страницы
            foreach ($this->page->blocks as $block) {
                $block->delete();
            }
            
            // Удаляем саму страницу
            $this->page->delete();
            $this->flashMessage(__('admin-pages.messages.delete_success'), 'success');
            $this->redirectTo('/admin/pages');
        } catch (\Exception $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }
} 