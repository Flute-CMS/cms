<?php

namespace Flute\Core\Modules\Installer\Components;

use Flute\Core\Database\DatabaseConnection;
use Flute\Core\Database\Entities\Role;
use Flute\Core\Database\Entities\User;
use Flute\Core\Modules\Installer\Services\InstallerConfig;
use Flute\Core\Support\FluteComponent;

class AdminUserComponent extends FluteComponent
{
    /**
     * @var string
     */
    public $name = '';

    /**
     * @var string
     */
    public $email = '';

    /**
     * @var string
     */
    public $login = '';

    /**
     * @var string
     */
    public $password = '';

    /**
     * @var string
     */
    public $password_confirmation = '';

    /**
     * @var string|null
     */
    public $errorMessage = null;

    /**
     * Create the admin user
     */
    public function createAdminUser()
    {
        try {
            $this->errorMessage = null;

            $validator = $this->validate([
                'name' => 'required|human-name|min-str-len:3|max-str-len:255',
                'email' => 'required|email|max-str-len:255',
                'login' => 'required|regex:/^[a-zA-Z0-9._-]+$/|min-str-len:6|max-str-len:20',
                'password' => 'required|min-str-len:8|confirmed',
                'password_confirmation' => 'required',
            ]);

            if (!$validator) {
                if (app(InstallerConfig::class)->getParams('admin_user_exists')) {
                    return $this->redirectTo(route('installer.step', ['id' => 6]), 500);
                }

                return;
            }

            app(DatabaseConnection::class)->recompileIfNeeded(true);

            $user = new User();
            $user->name = $this->name;
            $user->email = $this->email;
            $user->login = $this->login;
            $user->avatar = config('profile.default_avatar');
            $user->banner = config('profile.default_banner');

            $user->setPassword($this->password);

            $user->verified = true;

            $adminRole = Role::findOne(['name' => 'admin']);

            if ($adminRole) {
                $user->addRole($adminRole);
            }

            $user->save();

            app(InstallerConfig::class)->setParams([
                'admin_user_exists' => true,
            ]);

            return $this->redirectTo(route('installer.step', ['id' => 6]), 500);
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    /**
     * Render the component
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('installer::yoyo.admin-user', [
            'name' => $this->name,
            'email' => $this->email,
            'login' => $this->login,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
            'errorMessage' => $this->errorMessage,
        ]);
    }
}
