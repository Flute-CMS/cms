<?php

namespace Flute\Admin\Packages\Navigation\Screens;

use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Actions\DropDown;
use Flute\Admin\Platform\Actions\DropDownItem;
use Flute\Admin\Platform\Fields\CheckBox;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\Select;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Repository;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Database\Entities\NavbarItem;
use Flute\Core\Database\Entities\Role;
use Flute\Admin\Platform\Fields\Sight;

class NavigationListScreen extends Screen
{
    public ?string $name = null;
    public ?string $description = null;
    public ?string $permission = 'admin.navigation';

    public $navbarItems;
    public $roles;

    public function mount(): void
    {
        $this->name = __('admin-navigation.title');
        $this->description = __('admin-navigation.description');

        breadcrumb()
            ->add(__('def.admin_panel'), url('/admin'))
            ->add(__('admin-navigation.title'));

        $this->roles = rep(Role::class)->findAll();

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
                Sight::make('title', __('admin-navigation.table.title'))->render(fn(NavbarItem $navbarItem) => view('admin-navigation::cells.item-title', compact('navbarItem'))),
                Sight::make('actions', __('admin-navigation.table.actions'))
                    ->render(
                        fn(NavbarItem $navbarItem) => DropDown::make()
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
                            ])
                    ),
            ])->onSortEnd('updateNavbarItemPositions')
        ];
    }

    protected function loadNavbarItems()
    {
        $this->navbarItems = rep(NavbarItem::class)->select()->orderBy('position', 'asc')->where('parent_id', null)->load('children', [
            'load' => function ($qb) {
                $qb->orderBy('position', 'asc');
            }
        ])->fetchAll();
    }

    /**
     * Обновление позиций пунктов навигации после сортировки
     */
    public function updateNavbarItemPositions()
    {
        $sortableResult = json_decode(request()->input('sortableResult'), true);
        if (! $sortableResult) {
            $this->flashMessage(__('admin-navigation.messages.invalid_sort_data'), 'danger');
            return;
        }

        $this->reorderItems($sortableResult);

        orm()->getHeap()->clean();

        $this->loadNavbarItems();
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
            if (! $navbarItem) {
                continue;
            }

            $navbarItem->position = ++$position;
            $navbarItem->parent   = $parent;
            $navbarItem->save();

            if (! empty($item['children'])) {
                $this->reorderItems($item['children'], $navbarItem);
            }
        }
    }

    /**
     * Модальное окно для добавления нового пункта навигации
     */
    public function createNavbarItemModal(Repository $parameters)
    {
        $rolesCheckboxes = [];

        $priority = user()->getHighestPriority();

        foreach ($this->roles as $role) {
            if ($role->priority <= $priority) {
                $rolesCheckboxes[] = LayoutFactory::field(
                    CheckBox::make("roles.{$role->id}")
                        ->label($role->name)
                );
            }
        }

        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                Input::make('title')
                    ->type('text')
                    ->placeholder(__('admin-navigation.modal.item.fields.title.placeholder'))
            )
                ->label(__('admin-navigation.modal.item.fields.title.label'))
                ->required()
                ->small(__('admin-navigation.modal.item.fields.title.help')),

            LayoutFactory::field(
                Input::make('url')
                    ->type('text')
                    ->placeholder(__('admin-navigation.modal.item.fields.url.placeholder'))
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
                    ->placeholder(__('admin-navigation.modal.item.fields.icon.placeholder'))
            )
                ->label(__('admin-navigation.modal.item.fields.icon.label')),

            LayoutFactory::columns([
                LayoutFactory::field(
                    Select::make('visibility_auth')
                        ->options([
                            'all' => __('admin-navigation.modal.item.fields.visibility_auth.options.all'),
                            'guests' => __('admin-navigation.modal.item.fields.visibility_auth.options.guests'),
                            'logged_in' => __('admin-navigation.modal.item.fields.visibility_auth.options.logged_in'),
                        ])
                )
                    ->label(__('admin-navigation.modal.item.fields.visibility_auth.label'))
                    ->popover(__('admin-navigation.modal.item.fields.visibility_auth.help'))
                    ->required(),

                LayoutFactory::field(
                    Select::make('visibility')
                        ->options([
                            'all' => __('admin-navigation.modal.item.fields.visibility.options.all'),
                            'desktop' => __('admin-navigation.modal.item.fields.visibility.options.desktop'),
                            'mobile' => __('admin-navigation.modal.item.fields.visibility.options.mobile'),
                        ])
                )
                    ->label(__('admin-navigation.modal.item.fields.visibility.label'))
                    ->popover(__('admin-navigation.modal.item.fields.visibility.help'))
                    ->required(),
            ]),

            $rolesCheckboxes ?
                LayoutFactory::block([
                    LayoutFactory::split($rolesCheckboxes),
                ])->title(__('admin-navigation.modal.item.roles.title'))->popover(__('admin-navigation.modal.item.roles.help'))->addClass('navigation-modal-roles')
                : null,
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

        $validation = $this->validate([
            'title' => ['required', 'string', 'max-str-len:255'],
            'url' => ['nullable', 'string', 'max-str-len:255'],
            'icon' => ['nullable', 'string'],
            'visibility_auth' => ['required', 'in:all,guests,logged_in'],
            'visibility' => ['required', 'in:all,desktop,mobile'],
            'parent_id' => ['nullable', 'integer', 'exists:navbar_items,id'],
        ], $data);

        if (! $validation) {
            return;
        }

        $roles = [];
        foreach ($data as $key => $value) {
            if (strpos($key, 'roles_') === 0 && $value === 'on') {
                $roleId = str_replace('roles_', '', $key);
                if (is_numeric($roleId)) {
                    $roles[] = (int) $roleId;
                }
            }
        }

        $lastItem = NavbarItem::query()
            ->where('parent_id', $data['parent_id'] ?? null)
            ->orderBy('position', 'desc')
            ->fetchOne();
        $position = $lastItem ? $lastItem->position + 1 : 1;

        $navbarItem = new NavbarItem();
        $navbarItem->title = $data['title'];
        $navbarItem->url = $data['url'] ?? null;
        $navbarItem->new_tab = isset($data['new_tab']) && $data['new_tab'] ? true : false;
        $navbarItem->icon = $data['icon'] ?? null;
        $navbarItem->visibility = $data['visibility'];
        $navbarItem->position = $position;

        $navbarItem->visibleOnlyForGuests   = false;
        $navbarItem->visibleOnlyForLoggedIn = false;

        if ($data['visibility_auth'] === 'guests') {
            $navbarItem->visibleOnlyForGuests = true;
        } elseif ($data['visibility_auth'] === 'logged_in') {
            $navbarItem->visibleOnlyForLoggedIn = true;
        }

        $navbarItem->save();

        if (! empty($roles)) {
            foreach ($roles as $roleId) {
                $role = Role::findByPK($roleId);
                if ($role && user()->getHighestPriority() >= $role->priority) {
                    $navbarItem->addRole($role);
                }
            }
            $navbarItem->save();
        }

        $this->flashMessage(__('admin-navigation.messages.item_created'), 'success');
        $this->closeModal();
        $this->loadNavbarItems();
    }

    /**
     * Модальное окно для редактирования пункта навигации
     */
    public function editNavbarItemModal(Repository $parameters)
    {
        $navbarItemId = $parameters->get('navbarItem');
        $navbarItem = NavbarItem::findByPK($navbarItemId);
        if (! $navbarItem) {
            $this->flashMessage(__('admin-navigation.messages.item_not_found'), 'error');
            return;
        }

        $rolesCheckboxes = [];

        $priority = user()->getHighestPriority();

        $roles = array_map(fn($role) => $role->id, $navbarItem->roles);

        foreach ($this->roles as $role) {
            if ($role->priority <= $priority) {
                $rolesCheckboxes[] = LayoutFactory::field(
                    CheckBox::make("roles.{$role->id}")
                        ->label($role->name)
                        ->value(in_array($role->id, $roles))
                );
            }
        }

        $visibilityAuth = 'all';

        if ($navbarItem->visibleOnlyForGuests) {
            $visibilityAuth = 'guests';
        } elseif ($navbarItem->visibleOnlyForLoggedIn) {
            $visibilityAuth = 'logged_in';
        }

        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                Input::make('title')
                    ->type('text')
                    ->placeholder(__('admin-navigation.modal.item.fields.title.placeholder'))
                    ->value($navbarItem->title)
            )
                ->label(__('admin-navigation.modal.item.fields.title.label'))
                ->required()
                ->small(__('admin-navigation.modal.item.fields.title.help')),

            LayoutFactory::field(
                Input::make('url')
                    ->type('text')
                    ->placeholder(__('admin-navigation.modal.item.fields.url.placeholder'))
                    ->value($navbarItem->url)
            )
                ->label(__('admin-navigation.modal.item.fields.url.label'))
                ->small(__('admin-navigation.modal.item.fields.url.help')),

            LayoutFactory::field(
                CheckBox::make('new_tab')
                    ->label(__('admin-navigation.modal.item.fields.new_tab.label'))
                    ->popover(__('admin-navigation.modal.item.fields.new_tab.help'))
                    ->value($navbarItem->new_tab)
            ),

            LayoutFactory::field(
                Input::make('icon')
                    ->type('icon')
                    ->placeholder(__('admin-navigation.modal.item.fields.icon.placeholder'))
                    ->value($navbarItem->icon)
            )
                ->label(__('admin-navigation.modal.item.fields.icon.label')),

            LayoutFactory::columns([
                LayoutFactory::field(
                    Select::make('visibility_auth')
                        ->options([
                            'all' => __('admin-navigation.modal.item.fields.visibility_auth.options.all'),
                            'guests' => __('admin-navigation.modal.item.fields.visibility_auth.options.guests'),
                            'logged_in' => __('admin-navigation.modal.item.fields.visibility_auth.options.logged_in'),
                        ])
                        ->value($visibilityAuth)
                )
                    ->label(__('admin-navigation.modal.item.fields.visibility_auth.label'))
                    ->popover(__('admin-navigation.modal.item.fields.visibility_auth.help'))
                    ->required(),

                LayoutFactory::field(
                    Select::make('visibility')
                        ->options([
                            'all' => __('admin-navigation.modal.item.fields.visibility.options.all'),
                            'desktop' => __('admin-navigation.modal.item.fields.visibility.options.desktop'),
                            'mobile' => __('admin-navigation.modal.item.fields.visibility.options.mobile'),
                        ])
                        ->value($navbarItem->visibility)
                )
                    ->label(__('admin-navigation.modal.item.fields.visibility.label'))
                    ->popover(__('admin-navigation.modal.item.fields.visibility.help'))
                    ->required(),
            ]),

            $rolesCheckboxes ?
                LayoutFactory::block([
                    LayoutFactory::split($rolesCheckboxes),
                ])->title(__('admin-navigation.modal.item.roles.title'))->popover(__('admin-navigation.modal.item.roles.help'))->addClass('navigation-modal-roles')
                : null,
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
        if (! $navbarItem) {
            $this->flashMessage(__('admin-navigation.messages.item_not_found'), 'error');
            return;
        }

        $validation = $this->validate([
            'title' => ['required', 'string', 'max-str-len:255'],
            'url' => ['nullable', 'string', 'max-str-len:255'],
            'icon' => ['nullable', 'string'],
            'visibility_auth' => ['required', 'in:all,guests,logged_in'],
            'visibility' => ['required', 'in:all,desktop,mobile'],
            'parent_id' => ['nullable', 'integer', 'exists:navbar_items,id', "not_in:{$navbarItem->id}"],
        ], $data);

        if (! $validation) {
            return;
        }

        $roles = [];
        foreach ($data as $key => $value) {
            if (strpos($key, 'roles_') === 0 && $value === 'on') {
                $roleId = str_replace('roles_', '', $key);
                if (is_numeric($roleId)) {
                    $roles[] = (int) $roleId;
                }
            }
        }

        $navbarItem->title = $data['title'];
        $navbarItem->url = $data['url'] ?? null;
        $navbarItem->new_tab = isset($data['new_tab']) && $data['new_tab'] ? true : false;
        $navbarItem->icon = $data['icon'] ?? null;
        $navbarItem->visibility = $data['visibility'];

        // Reset flags to ensure mutually exclusive state
        $navbarItem->visibleOnlyForGuests   = false;
        $navbarItem->visibleOnlyForLoggedIn = false;

        if ($data['visibility_auth'] === 'guests') {
            $navbarItem->visibleOnlyForGuests = true;
        } elseif ($data['visibility_auth'] === 'logged_in') {
            $navbarItem->visibleOnlyForLoggedIn = true;
        }

        $navbarItem->save();

        $navbarItem->clearRoles();

        if (! empty($roles)) {
            foreach ($roles as $roleId) {
                $role = Role::findByPK($roleId);
                if ($role && user()->getHighestPriority() >= $role->priority) {
                    $navbarItem->addRole($role);
                }
            }
            $navbarItem->save();
        }

        $this->flashMessage(__('admin-navigation.messages.item_updated'), 'success');
        $this->closeModal();
        $this->loadNavbarItems();
    }

    /**
     * Удаление пункта навигации
     */
    public function deleteNavbarItem()
    {
        $id = request()->input('id');

        $navbarItem = NavbarItem::findByPK($id);
        if (! $navbarItem) {
            $this->flashMessage(__('admin-navigation.messages.item_not_found'), 'error');
            return;
        }

        if (! empty($navbarItem->children)) {
            $this->flashMessage(__('admin-navigation.messages.item_has_children'), 'warning');
            return;
        }

        $navbarItem->clearRoles();
        $navbarItem->save();

        $navbarItem->delete();
        $this->flashMessage(__('admin-navigation.messages.item_deleted'), 'success');
        $this->loadNavbarItems();
    }
}
