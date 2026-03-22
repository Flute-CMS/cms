<?php

namespace Flute\Admin\Packages\Roles\Screens;

use Cycle\Database\Injection\Parameter;
use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Actions\DropDown;
use Flute\Admin\Platform\Actions\DropDownItem;
use Flute\Admin\Platform\Fields\ButtonGroup;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\Select;
use Flute\Admin\Platform\Fields\Sight;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Repository;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Database\Entities\Permission;
use Flute\Core\Database\Entities\Role;
use Throwable;

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

        breadcrumb()->add(__('def.admin_panel'), url('/admin'))->add(__('admin-roles.breadcrumbs.roles'));

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
                Sight::make()->render(static fn(Role $role) => view('admin-roles::cells.role-name', compact('role'))),
                Sight::make('actions', __('admin-roles.table.actions'))->render(
                    static fn(Role $role) => DropDown::make()
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
                        ]),
                ),
            ])
                ->onSortEnd('saveRolePriority')
                ->maxLevels(1)
                ->empty('ph.regular.shield', __('admin-roles.empty.title'), __('admin-roles.empty.sub')),
        ];
    }

    public function saveRolePriority()
    {
        $sortableResult = json_decode(request()->input('sortableResult', '[]'), true);
        if (!$sortableResult) {
            $this->flashMessage(__('admin-roles.messages.invalid_sort'), 'error');

            return;
        }

        $this->reorderRoles($sortableResult);

        orm()->getHeap()->clean();

        $this->clearRoleRelatedCaches();

        $this->loadRoles();
    }

    /**
     * Модальное окно для добавления новой роли
     */
    public function createRoleModal(Repository $parameters)
    {
        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                Input::make('name')->type('text')->placeholder(__('admin-roles.fields.name.placeholder')),
            )
                ->label(__('admin-roles.fields.name.label'))
                ->required()
                ->small(__('admin-roles.fields.name.help')),

            LayoutFactory::field(
                Input::make('icon')->type('icon')->placeholder(__('admin-roles.fields.icon.placeholder')),
            )
                ->label(__('admin-roles.fields.icon.label'))
                ->small(__('admin-roles.fields.icon.help')),

            LayoutFactory::field(
                ButtonGroup::make('show_icon')
                    ->options([
                        '0' => ['label' => __('def.no'), 'icon' => 'ph.bold.x-bold'],
                        '1' => ['label' => __('def.yes'), 'icon' => 'ph.bold.check-bold'],
                    ])
                    ->value('0')
                    ->color('accent'),
            )
                ->label(__('admin-roles.fields.show_icon.label'))
                ->small(__('admin-roles.fields.show_icon.help')),

            LayoutFactory::field(Input::make('color')->type('color'))
                ->label(__('admin-roles.fields.color.label'))
                ->small(__('admin-roles.fields.color.help')),

            LayoutFactory::field(
                Select::make('permissions')
                    ->fromDatabase('permissions', 'name', 'name')
                    ->filter(static fn($permission) => user()->can($permission->name))
                    ->multiple(),
            )->label(__('admin-roles.fields.permissions.label')),
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

        if (!isset($data['permissions']) || !is_array($data['permissions'])) {
            $data['permissions'] = [];
        }

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
        $role->showIcon = isset($data['show_icon']) && filter_var($data['show_icon'], FILTER_VALIDATE_BOOLEAN);
        $role->priority = 0;

        $role->save();

        $role->clearPermissions();

        if (!empty($data['permissions'])) {
            $permissions = Permission::query()->where('name', 'IN', new Parameter($data['permissions']))->fetchAll();
            foreach ($permissions as $permission) {
                if (user()->can($permission->name)) {
                    $role->addPermission($permission);
                }
            }
        }

        $role->save();

        $this->clearRoleRelatedCaches();

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

        if (!$role || $role->priority >= user()->getHighestPriority()) {
            $this->flashMessage(__('admin-roles.messages.not_found'), 'error');

            return;
        }

        $selectedPermissions = array_map(static fn($permission) => $permission->name, $role->permissions);

        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                Input::make('name')
                    ->type('text')
                    ->placeholder(__('admin-roles.fields.name.placeholder'))
                    ->value($role->name),
            )
                ->label(__('admin-roles.fields.name.label'))
                ->required()
                ->small(__('admin-roles.fields.name.help')),

            LayoutFactory::field(
                Input::make('icon')
                    ->type('icon')
                    ->placeholder(__('admin-roles.fields.icon.placeholder'))
                    ->value($role->icon),
            )
                ->label(__('admin-roles.fields.icon.label'))
                ->small(__('admin-roles.fields.icon.help')),

            LayoutFactory::field(
                ButtonGroup::make('show_icon')
                    ->options([
                        '0' => ['label' => __('def.no'), 'icon' => 'ph.bold.x-bold'],
                        '1' => ['label' => __('def.yes'), 'icon' => 'ph.bold.check-bold'],
                    ])
                    ->value($role->showIcon ? '1' : '0')
                    ->color('accent'),
            )
                ->label(__('admin-roles.fields.show_icon.label'))
                ->small(__('admin-roles.fields.show_icon.help')),

            LayoutFactory::field(Input::make('color')->type('color')->value($role->color))
                ->label(__('admin-roles.fields.color.label'))
                ->small(__('admin-roles.fields.color.help')),

            LayoutFactory::field(
                Select::make('permissions')
                    ->fromDatabase('permissions', 'name', 'name')
                    ->filter(static fn($permission) => user()->can($permission->name))
                    ->multiple()
                    ->value($selectedPermissions),
            )->label(__('admin-roles.fields.permissions.label')),
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

        if (!$role || $role->priority >= user()->getHighestPriority()) {
            $this->flashMessage(__('admin-roles.messages.not_found'), 'error');

            return;
        }

        if (!isset($data['permissions']) || !is_array($data['permissions'])) {
            $data['permissions'] = [];
        }

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
        $role->showIcon = isset($data['show_icon']) && filter_var($data['show_icon'], FILTER_VALIDATE_BOOLEAN);

        $role->save();

        $role->clearPermissions();

        if (!empty($data['permissions'])) {
            $permissions = Permission::query()->where('name', 'IN', new Parameter($data['permissions']))->fetchAll();
            foreach ($permissions as $permission) {
                if (user()->can($permission->name)) {
                    $role->addPermission($permission);
                }
            }
        }

        // Persist clearing permissions even when none are selected
        $role->save();

        $this->clearRoleRelatedCaches();

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

        if (!$role || $role->priority >= user()->getHighestPriority()) {
            $this->flashMessage(__('admin-roles.messages.not_found'), 'error');

            return;
        }

        $role->delete();

        $this->clearRoleRelatedCaches();

        $this->flashMessage(__('admin-roles.messages.deleted'), 'success');
        $this->loadRoles();
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

    private function reorderRoles(array $roles)
    {
        $roleIds = array_map(fn($r) => (int) $r['id'], array_reverse($roles));
        $allRoles = Role::query()->where('id', 'IN', new Parameter($roleIds))->fetchAll();
        $rolesById = [];
        foreach ($allRoles as $role) {
            $rolesById[$role->id] = $role;
        }

        foreach (array_reverse($roles) as $index => $roleData) {
            $roleId = (int) $roleData['id'];
            $role = $rolesById[$roleId] ?? null;

            if (!$role || $role->priority >= user()->getHighestPriority()) {
                continue;
            }

            $role->priority = $index;

            $role->save();
        }
    }

    /**
     * Clear caches that depend on role data (navbar visibility is role-based).
     */
    private function clearRoleRelatedCaches(): void
    {
        try {
            cache()->deleteByTag(\Flute\Core\Services\NavbarService::CACHE_TAG);
            cache()->deleteImmediately('flute.global.layout');
        } catch (Throwable $e) {
            // Do not break admin flow if cache clearing fails
        }
    }
}
