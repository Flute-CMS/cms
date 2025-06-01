<?php

namespace Flute\Admin\Packages\Roles\Screens;

use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Actions\DropDown;
use Flute\Admin\Platform\Actions\DropDownItem;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\CheckBox;
use Flute\Admin\Platform\Fields\Sight;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Repository;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Database\Entities\Permission;
use Flute\Core\Database\Entities\Role;

class RoleListScreen extends Screen
{
    public ?string $name = null;
    public ?string $description = null;
    public ?string $permission = 'admin.roles';

    public $roles;

    public function mount(): void
    {
        $this->name = __('admin-roles.title.roles');
        $this->description = __('admin-roles.title.roles_description');

        breadcrumb()
            ->add(__('def.admin_panel'), url('/admin'))
            ->add(__('admin-roles.breadcrumbs.roles'));

        $this->loadRoles();
    }

    public function commandBar()
    {
        return [
            Button::make(__('admin-roles.buttons.create'))
                ->icon('ph.bold.plus-bold')
                ->size('medium')
                ->modal('createRoleModal')
                ->type(Color::PRIMARY),
        ];
    }

    public function layout(): array
    {
        return [
            LayoutFactory::sortable('roles', [
                Sight::make()->render(fn(Role $role) => view('admin-roles::cells.role-name', compact('role'))),
                Sight::make('actions', __('admin-roles.table.actions'))
                    ->render(
                        fn(Role $role) => DropDown::make()
                            ->icon('ph.regular.dots-three-outline-vertical')
                            ->list([
                                DropDownItem::make(__('admin-roles.buttons.edit'))
                                    ->modal('editRoleModal', ['role' => $role->id])
                                    ->icon('ph.bold.pencil-bold')
                                    ->type(Color::OUTLINE_PRIMARY)
                                    ->size('small')
                                    ->fullWidth(),
                                DropDownItem::make(__('admin-roles.buttons.delete'))
                                    ->confirm(__('admin-roles.modal.delete.confirm'))
                                    ->method('deleteRole', ['id' => $role->id])
                                    ->icon('ph.bold.trash-bold')
                                    ->type(Color::OUTLINE_DANGER)
                                    ->size('small')
                                    ->fullWidth(),
                            ])
                    ),
            ])->onSortEnd('saveRolePriority'),
        ];
    }

    public function saveRolePriority()
    {
        $sortableResult = json_decode(request()->input('sortableResult', '[]'), true);
        if (!$sortableResult) {
            $this->flashMessage(__('admin-roles.messages.invalid_sort'), 'danger');
            return;
        }

        $this->reorderRoles($sortableResult);
        $this->loadRoles();
    }

    private function reorderRoles(array $roles)
    {
        foreach (array_reverse($roles) as $index => $roleId) {
            $role = Role::findByPK((int) $roleId['id']);

            if (!$role || $role->priority > user()->getHighestPriority()) {
                continue;
            }

            $role->priority = $index;

            $role->save();
        }
    }

    protected function loadRoles()
    {
        $highestPriority = user()->getHighestPriority();

        $this->roles = rep(Role::class)
            ->select()
            ->where('priority', '<=', $highestPriority)
            ->orderBy('priority', 'desc')
            ->fetchAll();
    }

    /**
     * Модальное окно для добавления новой роли
     */
    public function createRoleModal(Repository $parameters)
    {
        $permissions = collect(Permission::findAll())
            ->filter(fn($permission) => user()->can($permission->name))
            ->pluck('name', 'id')
            ->toArray();

        $permissionsCheckboxes = [];
        foreach ($permissions as $id => $name) {
            $permissionsCheckboxes[] = LayoutFactory::field(
                CheckBox::make("permissions.$name")
                    ->label($name)
                    ->popover(__('permissions.' . $name))
            );
        }

        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                Input::make('name')
                    ->type('text')
                    ->placeholder(__('admin-roles.fields.name.placeholder'))
            )
                ->label(__('admin-roles.fields.name.label'))
                ->required()
                ->small(__('admin-roles.fields.name.help')),

            LayoutFactory::field(
                Input::make('icon')
                    ->type('icon')
                    ->placeholder(__('admin-roles.fields.icon.placeholder'))
            )
                ->label(__('admin-roles.fields.icon.label'))
                ->small(__('admin-roles.fields.icon.help')),

            LayoutFactory::field(
                Input::make('color')
                    ->type('color')
            )
                ->label(__('admin-roles.fields.color.label'))
                ->small(__('admin-roles.fields.color.help')),

            ...$permissionsCheckboxes
        ])
            ->title(__('admin-roles.modal.create.title'))
            ->applyButton(__('admin-roles.modal.create.submit'))
            ->method('saveRole');
    }

    /**
     * Сохранение новой роли
     */
    public function saveRole()
    {
        $data = request()->input();

        $permissions = [];
        foreach ($data as $key => $value) {
            if (strpos($key, 'permissions_') === 0 && $value == 'on') {
                $permissionName = substr($key, strlen('permissions_'));
                $permissions[] = str_replace('_', '.', $permissionName);
            }
        }

        $data['permissions'] = $permissions;

        // if (empty($data['permissions'])) {
        //     $this->flashMessage(__('admin-roles.messages.no_permissions'), 'error');
        //     return;
        // }

        $validation = $this->validate([
            'name' => ['required', 'string', 'max-str-len:255', 'unique:roles,name'],
            'icon' => ['nullable', 'string', 'max-str-len:255'],
            'color' => ['nullable', 'string', 'max-str-len:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,name'],
        ], $data);

        if (!$validation) {
            return;
        }

        $role = new Role();
        $role->name = $data['name'];
        $role->color = $data['color'] ?? null;
        $role->icon = $data['icon'] ?? null;
        $role->priority = 0;

        $role->save();

        $role->clearPermissions();

        if (!empty($data['permissions'])) {
            foreach ($data['permissions'] as $permissionId) {
                $permission = Permission::findOne(['name' => $permissionId]);
                if ($permission && user()->can($permission->name)) {
                    $role->addPermission($permission);
                }
            }
            $role->save();
        }

        $this->flashMessage(__('admin-roles.messages.created'), 'success');
        $this->closeModal();
        $this->loadRoles();
    }

    /**
     * Модальное окно для редактирования роли
     */
    public function editRoleModal(Repository $parameters)
    {
        $roleId = $parameters->get('role');
        $role = Role::findByPK($roleId);

        if (!$role || $role->priority > user()->getHighestPriority()) {
            $this->flashMessage(__('admin-roles.messages.not_found'), 'danger');
            return;
        }

        $permissions = collect(Permission::findAll())
            ->filter(fn($permission) => user()->can($permission->name))
            ->pluck('name', 'id')
            ->toArray();

        $permissionsCheckboxes = [];
        foreach ($permissions as $id => $name) {
            $permissionsCheckboxes[] = LayoutFactory::field(
                CheckBox::make("permissions.$name")
                    ->label($name)
                    ->popover(__('permissions.' . $name))
                    ->checked($role->hasPermission(Permission::findByPK($id)))
            );
        }

        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                Input::make('name')
                    ->type('text')
                    ->placeholder(__('admin-roles.fields.name.placeholder'))
                    ->value($role->name)
            )
                ->label(__('admin-roles.fields.name.label'))
                ->required()
                ->small(__('admin-roles.fields.name.help')),

            LayoutFactory::field(
                Input::make('icon')
                    ->type('icon')
                    ->placeholder(__('admin-roles.fields.icon.placeholder'))
                    ->value($role->icon)
            )
                ->label(__('admin-roles.fields.icon.label'))
                ->small(__('admin-roles.fields.icon.help')),

            LayoutFactory::field(
                Input::make('color')
                    ->type('color')
                    ->value($role->color)
            )
                ->label(__('admin-roles.fields.color.label'))
                ->small(__('admin-roles.fields.color.help')),

            ...$permissionsCheckboxes
        ])
            ->title(__('admin-roles.modal.edit.title'))
            ->applyButton(__('admin-roles.modal.edit.submit'))
            ->method('updateRole');
    }

    /**
     * Обновление существующей роли
     */
    public function updateRole()
    {
        $data = request()->input();
        $roleId = $this->modalParams->get('role');
        $role = Role::findByPK($roleId);

        if (!$role || $role->priority > user()->getHighestPriority()) {
            $this->flashMessage(__('admin-roles.messages.not_found'), 'danger');
            return;
        }

        $permissions = [];
        foreach ($data as $key => $value) {
            if (strpos($key, 'permissions_') === 0 && $value == 'on') {
                $permissionName = substr($key, strlen('permissions_'));
                $permissions[] = str_replace('_', '.', $permissionName);
            }
        }

        $data['permissions'] = $permissions;

        // if (empty($data['permissions'])) {
        //     $this->flashMessage(__('admin-roles.messages.no_permissions'), 'error');
        //     return;
        // }

        $validation = $this->validate([
            'name' => ['required', 'string', 'max-str-len:255', 'unique:roles,name,' . $role->id],
            'icon' => ['nullable', 'string', 'max-str-len:255'],
            'color' => ['nullable', 'string', 'max-str-len:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,name'],
        ], $data);

        if (!$validation) {
            return;
        }

        $role->name = $data['name'];
        $role->icon = $data['icon'] ?? null;
        $role->color = $data['color'] ?? null;

        $role->save();

        $role->clearPermissions();

        if (!empty($data['permissions'])) {
            foreach ($data['permissions'] as $permissionId) {
                $permission = Permission::findOne(['name' => $permissionId]);
                if ($permission && user()->can($permission->name)) {
                    $role->addPermission($permission);
                }
            }
            $role->save();
        }

        $this->flashMessage(__('admin-roles.messages.updated'), 'success');
        $this->closeModal();
        $this->loadRoles();
    }

    /**
     * Удаление роли
     */
    public function deleteRole()
    {
        $roleId = request()->input('id');
        $role = Role::findByPK($roleId);

        if (!$role || $role->priority > user()->getHighestPriority()) {
            $this->flashMessage(__('admin-roles.messages.not_found'), 'danger');
            return;
        }

        $role->delete();

        $this->flashMessage(__('admin-roles.messages.deleted'), 'success');
        $this->loadRoles();
    }
}
