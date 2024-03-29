<?php

namespace Flute\Core\Installer\Steps;

use Flute\Core\Database\Entities\Role;
use Flute\Core\Database\Entities\User;
use Flute\Core\Http\Controllers\APIInstallerController;
use Nette\Utils\Validators;
use Symfony\Component\HttpFoundation\Response;

class AdminStep extends AbstractStep
{
    public function install(\Flute\Core\Support\FluteRequest $request, APIInstallerController $installController): Response
    {
        $input = $request->input();

        if (!Validators::is($input['login'], 'string:2..'))
            return $installController->error($installController->trans('login_length'));

        if (!Validators::is($input['name'], 'string:2..'))
            return $installController->error($installController->trans('name_length'));

        if (!Validators::is($input['password'], 'string:4..'))
            return $installController->error($installController->trans('pass_length'));

        if (!Validators::isEmail($input['email']))
            return $installController->error($installController->trans('invalid_email'));

        if ($input['password'] !== $input['password_confirmation'] ?? '')
            return $installController->error($installController->trans('pass_diff'));

        // Очищаем всех пользователей перед добавлением нового. Вдруг он второй раз себя добавляет
        $this->clearAllUsers();

        if (!$this->createUser($input['login'], $input['name'], $input['email'], $input['password']))
            return $installController->error($installController->trans('error_create_user'));

        // $installController->setFinished(true);

        $installController->updateConfigStep('admin_login', $input['login']);

        return $installController->success();
    }

    protected function createUser(string $login, string $name, string $email, string $password): bool
    {
        $roleRepository = rep(Role::class);

        // Создание нового пользователя
        $user = new User;
        $user->name = $name;
        $user->login = $login;
        $user->email = $email;
        $user->avatar = 'assets/img/no_avatar.webp';
        $user->verified = true;
        $user->setPassword($password);

        // Получение роли "admin"
        $adminRole = $roleRepository->findOne(['name' => 'admin']);
        if ($adminRole) {
            $user->addRole($adminRole);
        } else {
            return false;
        }

        transaction($user)->run();

        return true;
    }

    protected function clearAllUsers(): void
    {
        db()->delete('users')->run();
    }
}