<?php

namespace Flute\Admin\Packages\ApiKey\Screens;

use Cycle\Database\Injection\Parameter;
use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Actions\DropDown;
use Flute\Admin\Platform\Actions\DropDownItem;
use Flute\Admin\Platform\Fields\CheckBox;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\TD;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Repository;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Database\Entities\ApiKey;
use Flute\Core\Database\Entities\Permission;
use Illuminate\Support\Str;

class ApiKeyScreen extends Screen
{
    public ?string $name = null;

    public ?string $description = null;

    public ?string $permission = 'admin.boss';

    public $apiKeys;

    public function mount(): void
    {
        $this->name = __('admin-apikey.title.list');
        $this->description = __('admin-apikey.title.description');

        breadcrumb()
            ->add(__('def.admin_panel'), url('/admin'))
            ->add(__('admin-apikey.title.list'));

        $this->apiKeys = rep(ApiKey::class)->select();
    }

    public function layout(): array
    {
        return [
            LayoutFactory::table('apiKeys', [
                TD::selection('id'),
                TD::make('id', 'ID')
                    ->minWidth('50px'),

                TD::make('name', __('admin-apikey.fields.name.label'))
                    ->render(static fn (ApiKey $apiKey) => $apiKey->name)
                    ->minWidth('250px')
                    ->cantHide(),

                TD::make('key', __('admin-apikey.fields.key.label'))
                    ->render(static fn (ApiKey $apiKey) => view('admin-api::cells.key', compact('apiKey')))
                    ->minWidth('250px'),

                TD::make('permissions', __('admin-apikey.fields.permissions.label'))
                    ->alignCenter()
                    ->render(static fn (ApiKey $apiKey) => sizeof($apiKey->permissions))
                    ->minWidth('100px'),

                TD::make('lastUsedAt', __('admin-apikey.fields.last_used_at'))
                    ->render(static fn (ApiKey $apiKey) => $apiKey->lastUsedAt ? $apiKey->lastUsedAt->format(default_date_format()) : __('admin-apikey.fields.never'))
                    ->minWidth('150px'),

                TD::make('createdAt', __('admin-apikey.fields.created_at'))
                    ->render(static fn (ApiKey $apiKey) => $apiKey->createdAt->format(default_date_format()))
                    ->minWidth('150px'),

                TD::make('actions', __('admin-apikey.buttons.actions'))
                    ->width('200px')
                    ->alignCenter()
                    ->render(static fn (ApiKey $apiKey) => DropDown::make()
                        ->icon('ph.regular.dots-three-outline-vertical')
                        ->list([
                            DropDownItem::make(__('admin-apikey.buttons.edit'))
                                ->modal('editApiKeyModal', ['id' => $apiKey->id])
                                ->icon('ph.bold.pencil-bold')
                                ->type(Color::OUTLINE_PRIMARY)
                                ->size('small')
                                ->fullWidth(),

                            DropDownItem::make(__('admin-apikey.buttons.delete'))
                                ->confirm(__('admin-apikey.confirms.delete_key'))
                                ->method('deleteApiKey', ['id' => $apiKey->id])
                                ->icon('ph.bold.trash-bold')
                                ->type(Color::OUTLINE_DANGER)
                                ->size('small')
                                ->fullWidth(),
                        ])),
            ])
                ->searchable(['key', 'id'])
                ->bulkActions([
                    Button::make(__('admin.bulk.delete_selected'))
                        ->icon('ph.bold.trash-bold')
                        ->type(Color::OUTLINE_DANGER)
                        ->confirm(__('admin.confirms.delete_selected'))
                        ->method('bulkDeleteApiKeys'),
                ])
                ->commands([
                    Button::make(__('admin-apikey.buttons.add'))
                        ->icon('ph.regular.plus')
                        ->type(Color::PRIMARY)
                        ->modal('addApiKeyModal'),
                ]),
        ];
    }

    /**
     * Модальное окно для добавления нового API ключа
     */
    public function addApiKeyModal(Repository $parameters)
    {
        $permissions = collect(Permission::findAll())
            ->filter(static fn ($permission) => user()->can($permission->name))
            ->pluck('name', 'id')
            ->toArray();

        $permissionsCheckboxes = [];
        foreach ($permissions as $id => $name) {
            $permissionsCheckboxes[] = LayoutFactory::field(
                CheckBox::make("permissions.{$name}")
                    ->label($name)
                    ->popover(__('permissions.'.$name))
            );
        }

        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                Input::make('key')
                    ->type('text')
                    ->placeholder(__('admin-apikey.fields.key.placeholder'))
                    ->value(Str::random(32))
            )
                ->label(__('admin-apikey.fields.key.label'))
                ->required()
                ->small(__('admin-apikey.fields.key.help')),

            LayoutFactory::field(
                Input::make('name')
                    ->type('text')
                    ->placeholder(__('admin-apikey.fields.name.placeholder'))
            )
                ->label(__('admin-apikey.fields.name.label'))
                ->required()
                ->small(__('admin-apikey.fields.name.help')),

            ...$permissionsCheckboxes,
        ])
            ->title(__('admin-apikey.title.create'))
            ->applyButton(__('admin-apikey.buttons.add'))
            ->method('saveApiKey');
    }

    /**
     * Сохранение нового API ключа
     */
    public function saveApiKey()
    {
        $data = request()->input();

        $permissions = [];
        foreach ($data as $key => $value) {
            if (strpos($key, 'permissions_') === 0 && $value == 'on') {
                $permissionName = substr($key, strlen('permissions_'));
                $permissionName = str_replace('_', '.', $permissionName);
                $permissions[] = $permissionName;
            }
        }

        $data['permissions'] = $permissions;

        $validation = $this->validate([
            'key' => ['required', 'string', 'unique:api_keys,key'],
            'name' => ['required', 'string', 'unique:api_keys,name'],
            'permissions' => ['required', 'array'],
            // 'permissions.*' => ['exists:permissions,name'],
        ], $data);

        if (!$validation) {
            return;
        }

        $apiKey = new ApiKey();
        $apiKey->key = $data['key'];
        $apiKey->name = $data['name'];
        $apiKey->saveOrFail();

        $permissions = Permission::query()->where('name', 'in', new Parameter($data['permissions']))->fetchAll();
        foreach ($permissions as $permission) {
            if (user()->can($permission->name)) {
                $apiKey->addPermission($permission);
            }
        }
        $apiKey->saveOrFail();

        $this->flashMessage(__('admin-apikey.messages.save_success'), 'success');
        $this->apiKeys = rep(ApiKey::class)->select();
        $this->closeModal();
    }

    /**
     * Модальное окно для редактирования API ключа
     */
    public function editApiKeyModal(Repository $parameters)
    {
        $apiKey = ApiKey::findByPK($parameters->get('id'));
        if (!$apiKey) {
            $this->flashMessage(__('admin-apikey.messages.key_not_found'), 'danger');

            return;
        }

        $permissions = collect(Permission::findAll())
            ->filter(static fn ($permission) => user()->can($permission->name))
            ->pluck('name', 'id')
            ->toArray();

        $permissionsCheckboxes = [];
        foreach ($permissions as $id => $name) {
            $permissionsCheckboxes[] = LayoutFactory::field(
                CheckBox::make("permissions.{$name}")
                    ->label($name)
                    ->popover(__('permissions.'.$name))
                    ->checked($apiKey->hasPermissionByName($name))
                    ->value(1)
            );
        }

        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                Input::make('key')
                    ->type('text')
                    ->placeholder(__('admin-apikey.fields.key.placeholder'))
                    ->value($apiKey->key)
            )
                ->label(__('admin-apikey.fields.key.label'))
                ->required()
                ->small(__('admin-apikey.fields.key.help')),

            LayoutFactory::field(
                Input::make('name')
                    ->type('text')
                    ->placeholder(__('admin-apikey.fields.name.placeholder'))
                    ->value($apiKey->name)
            )
                ->label(__('admin-apikey.fields.name.label'))
                ->required()
                ->small(__('admin-apikey.fields.name.help')),

            ...$permissionsCheckboxes,
        ])
            ->title(__('admin-apikey.title.edit'))
            ->applyButton(__('admin-apikey.buttons.save'))
            ->method('updateApiKey');
    }

    /**
     * Обновление существующего API ключа
     */
    public function updateApiKey()
    {
        $data = request()->input();
        $id = $this->modalParams->get('id');

        $permissions = [];
        foreach ($data as $key => $value) {
            if (strpos($key, 'permissions_') === 0 && $value == 'on') {
                $permissionName = substr($key, strlen('permissions_'));
                $permissions[] = str_replace('_', '.', $permissionName);
            }
        }

        $data['permissions'] = $permissions;

        if (empty($data['permissions'])) {
            $this->flashMessage(__('admin-apikey.messages.no_permissions'), 'error');

            return;
        }

        $apiKey = ApiKey::findByPK($id);
        if (!$apiKey) {
            $this->flashMessage(__('admin-apikey.messages.key_not_found'), 'danger');

            return;
        }

        $validation = $this->validate([
            'key' => ['required', 'string', "unique:api_keys,key,{$apiKey->id}"],
            'name' => ['required', 'string'],
            'permissions' => ['required', 'array'],
            // 'permissions.*' => ['exists:permissions,name'],
        ], $data);

        if (!$validation) {
            return;
        }

        $apiKey->key = $data['key'];
        $apiKey->name = $data['name'];
        $apiKey->saveOrFail();

        $apiKey->permissions = [];

        $permissions = Permission::query()->where('name', 'in', new Parameter($data['permissions']))->fetchAll();

        foreach ($permissions as $permission) {
            if (user()->can($permission->name)) {
                $apiKey->addPermission($permission);
            }
        }

        $apiKey->save();

        $this->flashMessage(__('admin-apikey.messages.save_success'), 'success');
        $this->apiKeys = rep(ApiKey::class)->select();
        $this->closeModal();
    }

    /**
     * Удаление API ключа
     */
    public function deleteApiKey()
    {
        $id = request()->input('id');

        $apiKey = ApiKey::findByPK($id);
        if (!$apiKey) {
            $this->flashMessage(__('admin-apikey.messages.key_not_found'), 'danger');

            return;
        }

        $apiKey->delete();
        $this->flashMessage(__('admin-apikey.messages.delete_success'), 'success');
        $this->apiKeys = rep(ApiKey::class)->select();
    }

    public function bulkDeleteApiKeys(): void
    {
        $ids = request()->input('selected', []);
        if (!$ids) {
            return;
        }
        foreach ($ids as $id) {
            $apiKey = ApiKey::findByPK($id);
            if (!$apiKey) {
                continue;
            }
            $apiKey->delete();
        }
        $this->apiKeys = rep(ApiKey::class)->select();
        $this->flashMessage(__('admin-apikey.messages.delete_success'), 'success');
    }
}
