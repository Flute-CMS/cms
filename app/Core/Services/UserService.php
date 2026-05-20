<?php

namespace Flute\Core\Services;

use Flute\Core\Database\Entities\User;
use Flute\Core\Modules\Auth\Services\AuthService;
use Flute\Core\Services\Concerns\UserService\HandlesAuthSession;
use Flute\Core\Services\Concerns\UserService\HandlesUserBalance;
use Flute\Core\Services\Concerns\UserService\HandlesUserLookup;
use Flute\Core\Services\Concerns\UserService\HandlesUserMagic;
use Flute\Core\Services\Concerns\UserService\HandlesUserPermissions;
use Flute\Core\Services\Concerns\UserService\HandlesUserUpdate;
use Jenssegers\Agent\Agent;

enum UserPermission: string
{
    case ADMIN_BOSS = 'admin.boss';
}

class UserService
{
    use HandlesAuthSession;
    use HandlesUserBalance;
    use HandlesUserLookup;
    use HandlesUserMagic;
    use HandlesUserPermissions;
    use HandlesUserUpdate;

    protected ?string $userToken = null;
    protected ?Agent $userDevice = null;
    protected bool $triedToLogin = false;
    protected ?User $currentUser = null;
    protected readonly AuthService $authService;
    protected array $usersCache = [];
    protected ?array $permissionsCache = null;
    protected ?array $rolesCache = null;
    protected ?int $highestPriority = null;
    protected bool $permissionFailureLogged = false;
    protected bool $authFailureLogged = false;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
        $this->userToken = cookie()->get('remember_token');
        $this->userDevice = $this->device();
        $this->triedToLogin = false;
    }
}
