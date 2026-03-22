<?php

namespace Flute\Admin\Packages\Navigation\Screens;

use Cycle\Database\Injection\Parameter;
use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Actions\DropDown;
use Flute\Admin\Platform\Actions\DropDownItem;
use Flute\Admin\Platform\Fields\CheckBox;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\RadioCards;
use Flute\Admin\Platform\Fields\Select;
use Flute\Admin\Platform\Fields\Sight;
use Flute\Admin\Platform\Fields\TranslatableInput;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Repository;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Database\Entities\NavbarItem;
use Flute\Core\Database\Entities\Role;
use Throwable;

class NavigationListScreen extends Screen
{
    public ?string $name = null;

    public ?string $description = null;

    public ?string $permission = 'admin.navigation';

    public $navbarItems;

    public function mount(): void
    {
        $this->name = __('admin-navigation.title');
        $this->description = __('admin-navigation.description');

        breadcrumb()->add(__('def.admin_panel'), url('/admin'))->add(__('admin-navigation.title'));

        $this->loadNavbarItems();
    }

    public function commandBar()
    {
        return [
            Button::make(__('admin-navigation.buttons.create'))
                ->icon('ph.bold.plus-bold')
                ->size('medium')
                ->modal('createNavbarItemModal')
                ->type(Color::PRIMARY),
        ];
    }

    public function layout(): array
    {
        return [
            LayoutFactory::sortable('navbarItems', [
                Sight::make(
                    'title',
                    __('admin-navigation.table.title'),
                )->render(static fn(NavbarItem $navbarItem) => view(
                    'admin-navigation::cells.item-title',
                    compact('navbarItem'),
                )),
                Sight::make('actions', __('admin-navigation.table.actions'))->render(
                    static fn(NavbarItem $navbarItem) => DropDown::make()
                        ->icon('ph.regular.dots-three-outline-vertical')
                        ->list([
                            DropDownItem::make(__('admin-navigation.buttons.edit'))
                                ->modal('editNavbarItemModal', ['navbarItem' => $navbarItem->id])
                                ->icon('ph.bold.pencil-bold')
                                ->type(Color::OUTLINE_PRIMARY)
                                ->size('small')
                                ->fullWidth(),
                            DropDownItem::make(__('admin-navigation.buttons.delete'))
                                ->confirm(__('admin-navigation.confirms.delete_item'))
                                ->method('deleteNavbarItem', ['id' => $navbarItem->id])
                                ->icon('ph.bold.trash-bold')
                                ->type(Color::OUTLINE_DANGER)
                                ->size('small')
                                ->fullWidth(),
                        ]),
                ),
            ])
                ->maxLevels(levels: 3)
                ->onSortEnd('updateNavbarItemPositions')
                ->empty('ph.regular.list', __('admin-navigation.empty.title'), __('admin-navigation.empty.sub')),
        ];
    }

    /**
     * Обновление позиций пунктов навигации после сортировки
     */
    public function updateNavbarItemPositions()
    {
        $sortableResult = json_decode(request()->input('sortableResult'), true);
        if (!$sortableResult) {
            $this->flashMessage(__('admin-navigation.messages.invalid_sort_data'), 'error');

            return;
        }

        $this->reorderItems($sortableResult);

        orm()->getHeap()->clean();

        $this->clearNavbarCache();

        $this->loadNavbarItems();
    }

    /**
     * Модальное окно для добавления нового пункта навигации
     */
    public function createNavbarItemModal(Repository $parameters)
    {
        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                TranslatableInput::make('title')
                    ->type('text')
                    ->placeholder(__('admin-navigation.modal.item.fields.title.placeholder')),
            )
                ->label(__('admin-navigation.modal.item.fields.title.label'))
                ->required()
                ->small(__('admin-navigation.modal.item.fields.title.help')),

            LayoutFactory::field(
                TranslatableInput::make('description')
                    ->type('text')
                    ->placeholder(__('admin-navigation.modal.item.fields.description.placeholder')),
            )
                ->label(__('admin-navigation.modal.item.fields.description.label'))
                ->small(__('admin-navigation.modal.item.fields.description.help')),

            LayoutFactory::field(
                Input::make('url')->type('text')->placeholder(__('admin-navigation.modal.item.fields.url.placeholder')),
            )
                ->label(__('admin-navigation.modal.item.fields.url.label'))
                ->small(__('admin-navigation.modal.item.fields.url.help')),

            LayoutFactory::field(
                CheckBox::make('new_tab')
                    ->label(__('admin-navigation.modal.item.fields.new_tab.label'))
                    ->popover(__('admin-navigation.modal.item.fields.new_tab.help')),
            ),

            LayoutFactory::field(
                Input::make('icon')
                    ->type('icon')
                    ->placeholder(__('admin-navigation.modal.item.fields.icon.placeholder')),
            )->label(__('admin-navigation.modal.item.fields.icon.label')),

            LayoutFactory::columns([
                LayoutFactory::field(
                    RadioCards::make('visibility_auth')
                        ->options([
                            'all' => [
                                'icon' => 'ph.bold.users-bold',
                                'label' => __('admin-navigation.modal.item.fields.visibility_auth.options.all'),
                            ],
                            'guests' => [
                                'icon' => 'ph.bold.user-bold',
                                'label' => __('admin-navigation.modal.item.fields.visibility_auth.options.guests'),
                            ],
                            'logged_in' => [
                                'icon' => 'ph.bold.user-check-bold',
                                'label' => __('admin-navigation.modal.item.fields.visibility_auth.options.logged_in'),
                            ],
                        ])
                        ->columns(3)
                        ->value('all'),
                )
                    ->label(__('admin-navigation.modal.item.fields.visibility_auth.label'))
                    ->popover(__('admin-navigation.modal.item.fields.visibility_auth.help'))
                    ->required(),

                LayoutFactory::field(
                    RadioCards::make('visibility')
                        ->options([
                            'all' => [
                                'icon' => 'ph.bold.devices-bold',
                                'label' => __('admin-navigation.modal.item.fields.visibility.options.all'),
                            ],
                            'desktop' => [
                                'icon' => 'ph.bold.desktop-bold',
                                'label' => __('admin-navigation.modal.item.fields.visibility.options.desktop'),
                            ],
                            'mobile' => [
                                'icon' => 'ph.bold.device-mobile-bold',
                                'label' => __('admin-navigation.modal.item.fields.visibility.options.mobile'),
                            ],
                        ])
                        ->columns(3)
                        ->value('all'),
                )
                    ->label(__('admin-navigation.modal.item.fields.visibility.label'))
                    ->popover(__('admin-navigation.modal.item.fields.visibility.help'))
                    ->required(),
            ]),

            LayoutFactory::field(Select::make('roles')
                ->fromDatabase('roles', 'name', 'id', ['name', 'id', 'priority'])
                ->multiple()
                ->filter(static function ($role) {
                    if (user()->can('admin.boss')) {
                        return true;
                    }

                    return $role->priority <= user()->getHighestPriority();
                }))
                ->label(__('admin-navigation.modal.item.roles.title'))
                ->popover(__('admin-navigation.modal.item.roles.help')),
        ])
            ->title(__('admin-navigation.modal.item.create_title'))
            ->applyButton(__('admin-navigation.buttons.create'))
            ->method('saveNavbarItem');
    }

    /**
     * Сохранение нового пункта навигации
     */
    public function saveNavbarItem()
    {
        $data = request()->input();

        if (empty(transValue($data['title'] ?? ''))) {
            $this->flashMessage(__('validator.required', ['attribute' => 'title']), 'error');

            return;
        }

        $validation = $this->validate([
            'title' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'url' => ['nullable', 'string', 'max-str-len:255'],
            'icon' => ['nullable', 'string'],
            'visibility_auth' => ['required', 'in:all,guests,logged_in'],
            'visibility' => ['required', 'in:all,desktop,mobile'],
            'parent_id' => ['nullable', 'integer', 'exists:navbar_items,id'],
        ], $data);

        if (!$validation) {
            return;
        }

        $roles = $this->extractRoleIds($data);

        $lastItem = NavbarItem::query()
            ->where('parent_id', $data['parent_id'] ?? null)
            ->orderBy('position', 'desc')
            ->fetchOne();
        $position = $lastItem ? $lastItem->position + 1 : 1;

        $navbarItem = new NavbarItem();
        $navbarItem->title = $data['title'];
        $navbarItem->description = $data['description'] ?? null;
        $navbarItem->url = $data['url'] ?? null;
        $navbarItem->new_tab = isset($data['new_tab']) && $data['new_tab'] ? true : false;
        $navbarItem->icon = $data['icon'] ?? null;
        $navbarItem->visibility = $data['visibility'];
        $navbarItem->position = $position;

        $navbarItem->visibleOnlyForGuests = false;
        $navbarItem->visibleOnlyForLoggedIn = false;

        if ($data['visibility_auth'] === 'guests') {
            $navbarItem->visibleOnlyForGuests = true;
        } elseif ($data['visibility_auth'] === 'logged_in') {
            $navbarItem->visibleOnlyForLoggedIn = true;
        }

        $navbarItem->save();

        if (!empty($roles)) {
            $fetchedRoles = Role::query()->where('id', 'IN', new Parameter($roles))->fetchAll();
            foreach ($fetchedRoles as $role) {
                if (user()->getHighestPriority() >= $role->priority) {
                    $navbarItem->addRole($role);
                }
            }
            $navbarItem->save();
        }

        $this->flashMessage(__('admin-navigation.messages.item_created'), 'success');
        $this->closeModal();

        $this->clearNavbarCache();

        $this->loadNavbarItems();
    }

    /**
     * Модальное окно для редактирования пункта навигации
     */
    public function editNavbarItemModal(Repository $parameters)
    {
        $navbarItemId = $parameters->get('navbarItem');
        $navbarItem = NavbarItem::findByPK($navbarItemId);
        if (!$navbarItem) {
            $this->flashMessage(__('admin-navigation.messages.item_not_found'), 'error');

            return;
        }

        $visibilityAuth = 'all';

        if ($navbarItem->visibleOnlyForGuests) {
            $visibilityAuth = 'guests';
        } elseif ($navbarItem->visibleOnlyForLoggedIn) {
            $visibilityAuth = 'logged_in';
        }

        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                TranslatableInput::make('title')
                    ->type('text')
                    ->placeholder(__('admin-navigation.modal.item.fields.title.placeholder'))
                    ->value($navbarItem->title),
            )
                ->label(__('admin-navigation.modal.item.fields.title.label'))
                ->required()
                ->small(__('admin-navigation.modal.item.fields.title.help')),

            LayoutFactory::field(
                TranslatableInput::make('description')
                    ->type('text')
                    ->placeholder(__('admin-navigation.modal.item.fields.description.placeholder'))
                    ->value($navbarItem->description),
            )
                ->label(__('admin-navigation.modal.item.fields.description.label'))
                ->small(__('admin-navigation.modal.item.fields.description.help')),

            LayoutFactory::field(
                Input::make('url')
                    ->type('text')
                    ->placeholder(__('admin-navigation.modal.item.fields.url.placeholder'))
                    ->value($navbarItem->url),
            )
                ->label(__('admin-navigation.modal.item.fields.url.label'))
                ->small(__('admin-navigation.modal.item.fields.url.help')),

            LayoutFactory::field(
                CheckBox::make('new_tab')
                    ->label(__('admin-navigation.modal.item.fields.new_tab.label'))
                    ->popover(__('admin-navigation.modal.item.fields.new_tab.help'))
                    ->value($navbarItem->new_tab),
            ),

            LayoutFactory::field(
                Input::make('icon')
                    ->type('icon')
                    ->placeholder(__('admin-navigation.modal.item.fields.icon.placeholder'))
                    ->value($navbarItem->icon),
            )->label(__('admin-navigation.modal.item.fields.icon.label')),

            LayoutFactory::columns([
                LayoutFactory::field(
                    RadioCards::make('visibility_auth')
                        ->options([
                            'all' => [
                                'icon' => 'ph.bold.users-bold',
                                'label' => __('admin-navigation.modal.item.fields.visibility_auth.options.all'),
                            ],
                            'guests' => [
                                'icon' => 'ph.bold.user-bold',
                                'label' => __('admin-navigation.modal.item.fields.visibility_auth.options.guests'),
                            ],
                            'logged_in' => [
                                'icon' => 'ph.bold.user-check-bold',
                                'label' => __('admin-navigation.modal.item.fields.visibility_auth.options.logged_in'),
                            ],
                        ])
                        ->columns(3)
                        ->value($visibilityAuth),
                )
                    ->label(__('admin-navigation.modal.item.fields.visibility_auth.label'))
                    ->popover(__('admin-navigation.modal.item.fields.visibility_auth.help'))
                    ->required(),

                LayoutFactory::field(
                    RadioCards::make('visibility')
                        ->options([
                            'all' => [
                                'icon' => 'ph.bold.devices-bold',
                                'label' => __('admin-navigation.modal.item.fields.visibility.options.all'),
                            ],
                            'desktop' => [
                                'icon' => 'ph.bold.desktop-bold',
                                'label' => __('admin-navigation.modal.item.fields.visibility.options.desktop'),
                            ],
                            'mobile' => [
                                'icon' => 'ph.bold.device-mobile-bold',
                                'label' => __('admin-navigation.modal.item.fields.visibility.options.mobile'),
                            ],
                        ])
                        ->columns(3)
                        ->value($navbarItem->visibility),
                )
                    ->label(__('admin-navigation.modal.item.fields.visibility.label'))
                    ->popover(__('admin-navigation.modal.item.fields.visibility.help'))
                    ->required(),
            ]),

            LayoutFactory::field(Select::make('roles')
                ->fromDatabase('roles', 'name', 'id', ['name', 'id', 'priority'])
                ->multiple()
                ->value($navbarItem->roles)
                ->filter(static function ($role) {
                    if (user()->can('admin.boss')) {
                        return true;
                    }

                    return $role->priority <= user()->getHighestPriority();
                }))
                ->label(__('admin-navigation.modal.item.roles.title'))
                ->popover(__('admin-navigation.modal.item.roles.help')),
        ])
            ->title(__('admin-navigation.modal.item.edit_title'))
            ->applyButton(__('def.save'))
            ->method('updateNavbarItem');
    }

    /**
     * Обновление существующего пункта навигации
     */
    public function updateNavbarItem()
    {
        $data = request()->input();
        $navbarItemId = $this->modalParams->get('navbarItem');

        $navbarItem = NavbarItem::findByPK($navbarItemId);
        if (!$navbarItem) {
            $this->flashMessage(__('admin-navigation.messages.item_not_found'), 'error');

            return;
        }

        if (empty(transValue($data['title'] ?? ''))) {
            $this->flashMessage(__('validator.required', ['attribute' => 'title']), 'error');

            return;
        }

        $validation = $this->validate([
            'title' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'url' => ['nullable', 'string', 'max-str-len:255'],
            'icon' => ['nullable', 'string'],
            'visibility_auth' => ['required', 'in:all,guests,logged_in'],
            'visibility' => ['required', 'in:all,desktop,mobile'],
            'parent_id' => ['nullable', 'integer', 'exists:navbar_items,id', "not_in:{$navbarItem->id}"],
        ], $data);

        if (!$validation) {
            return;
        }

        $roles = $this->extractRoleIds($data);

        $navbarItem->title = $data['title'];
        $navbarItem->description = $data['description'] ?? null;
        $navbarItem->url = $data['url'] ?? null;
        $navbarItem->new_tab = isset($data['new_tab']) && $data['new_tab'] ? true : false;
        $navbarItem->icon = $data['icon'] ?? null;
        $navbarItem->visibility = $data['visibility'];

        // Reset flags to ensure mutually exclusive state
        $navbarItem->visibleOnlyForGuests = false;
        $navbarItem->visibleOnlyForLoggedIn = false;

        if ($data['visibility_auth'] === 'guests') {
            $navbarItem->visibleOnlyForGuests = true;
        } elseif ($data['visibility_auth'] === 'logged_in') {
            $navbarItem->visibleOnlyForLoggedIn = true;
        }

        $navbarItem->save();

        $navbarItem->clearRoles();

        if (!empty($roles)) {
            $fetchedRoles = Role::query()->where('id', 'IN', new Parameter($roles))->fetchAll();
            foreach ($fetchedRoles as $role) {
                if (user()->getHighestPriority() >= $role->priority) {
                    $navbarItem->addRole($role);
                }
            }
        }

        $navbarItem->save();

        $this->flashMessage(__('admin-navigation.messages.item_updated'), 'success');
        $this->closeModal();

        $this->clearNavbarCache();

        $this->loadNavbarItems();
    }

    /**
     * Удаление пункта навигации
     */
    public function deleteNavbarItem()
    {
        $id = request()->input('id');

        $navbarItem = NavbarItem::findByPK($id);
        if (!$navbarItem) {
            $this->flashMessage(__('admin-navigation.messages.item_not_found'), 'error');

            return;
        }

        if (!empty($navbarItem->children)) {
            $this->flashMessage(__('admin-navigation.messages.item_has_children'), 'warning');

            return;
        }

        $navbarItem->clearRoles();
        $navbarItem->save();

        $navbarItem->delete();
        $this->flashMessage(__('admin-navigation.messages.item_deleted'), 'success');

        $this->clearNavbarCache();

        $this->loadNavbarItems();
    }

    protected function loadNavbarItems()
    {
        $allItems = rep(NavbarItem::class)->select()->orderBy('position', 'asc')->fetchAll();

        $itemsById = [];
        foreach ($allItems as $item) {
            $itemsById[$item->id] = $item;
            $item->children = [];
        }

        $rootItems = [];
        foreach ($allItems as $item) {
            if ($item->parent === null) {
                $rootItems[] = $item;
            } else {
                $parentId = $item->parent->id ?? null;
                if ($parentId && isset($itemsById[$parentId])) {
                    $itemsById[$parentId]->children[] = $item;
                }
            }
        }

        $this->navbarItems = $rootItems;
    }

    /**
     * Recalculate positions recursively without sharing the counter between sibling groups.
     * This guarantees that each parent has its own contiguous ordering so the UI doesn't get
     * out of sync after drag-and-drop.
     */
    private function reorderItems(array $items, ?NavbarItem $parent = null): void
    {
        $position = 0;

        foreach ($items as $item) {
            $navbarItem = NavbarItem::findByPK($item['id']);
            if (!$navbarItem) {
                continue;
            }

            $navbarItem->position = ++$position;
            $navbarItem->parent = $parent;
            $navbarItem->save();

            if (!empty($item['children'])) {
                $this->reorderItems($item['children'], $navbarItem);
            }
        }
    }

    /**
     * Clear all cached navbar keys and global layout.
     */
    private function clearNavbarCache(): void
    {
        try {
            cache()->deleteByTag(\Flute\Core\Services\NavbarService::CACHE_TAG);
            cache()->deleteImmediately('flute.global.layout');
        } catch (Throwable $e) {
            // Do not break admin flow if cache clearing fails
        }
    }

    private function extractRoleIds(array $data): array
    {
        if (!isset($data['roles'])) {
            return [];
        }

        $raw = $data['roles'];

        // Single value (string/int)
        if (!is_array($raw)) {
            $id = (int) $raw;

            return $id > 0 ? [$id] : [];
        }

        $roles = [];

        foreach ($raw as $key => $value) {
            // Flat array from multiselect: [0 => '7', 1 => '8', ...]
            if (is_numeric($value) && (int) $value > 0) {
                $roles[] = (int) $value;

                continue;
            }

            // Legacy checkbox format: roles[{id}] => 'on'
            if (is_numeric($key) && ( $value === 'on' || $value === true || $value === 1 || $value === '1' )) {
                $roles[] = (int) $key;
            }
        }

        return array_values(array_unique($roles));
    }
}
