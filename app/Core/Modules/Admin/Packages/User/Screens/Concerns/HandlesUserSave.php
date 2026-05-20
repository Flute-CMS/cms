<?php

namespace Flute\Admin\Packages\User\Screens\Concerns;

use Throwable;

trait HandlesUserSave
{
    public function saveUser()
    {
        if (!$this->canEditDisplayedUser()) {
            $this->flashMessage(__('admin-users.messages.no_permission'), 'error');

            return;
        }

        $data = request()->input();
        $files = request()->files;

        $removeAvatar = ( $data['avatar_clear'] ?? '0' ) === '1';
        $removeBanner = ( $data['banner_clear'] ?? '0' ) === '1';

        unset($data['avatar'], $data['banner'], $data['avatar_clear'], $data['banner_clear']);

        if (isset($data['roles']) && !user()->can('admin.roles')) {
            $this->flashMessage(__('admin-users.messages.no_permission_roles'), 'error');

            return;
        }

        $rules = [
            'name' => ['required', 'string', 'max-str-len:255'],
            'login' => ['nullable', 'string', 'max-str-len:255', 'unique:users,login,' . $this->userId],
            'uri' => ['nullable', 'string', 'max-str-len:255', 'unique:users,uri,' . $this->userId],
            'email' => ['nullable', 'email', 'max-str-len:255', 'unique:users,email,' . $this->userId],
            'balance' => ['required', 'numeric', 'min:0'],
            'roles' => ['nullable', 'array'],
            'verified' => ['sometimes', 'boolean'],
            'hidden' => ['sometimes', 'boolean'],
        ];

        if ($files->has('avatar') && $files->get('avatar')->isValid()) {
            $rules['avatar'] = ['nullable', 'image', 'max-file-size:10'];
        }

        if ($files->has('banner') && $files->get('banner')->isValid()) {
            $rules['banner'] = ['nullable', 'image', 'max-file-size:10'];
        }

        $validation = $this->validate($rules, $data);

        if (!$validation) {
            return;
        }

        try {
            $this->usersService->saveUser($this->user, $data, $files, $removeAvatar, $removeBanner);
            $this->flashMessage(__('admin-users.messages.save_success'), 'success');
            $this->initUser();
        } catch (Throwable $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }
}
