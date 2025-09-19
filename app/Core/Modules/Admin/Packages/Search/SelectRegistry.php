<?php

namespace Flute\Admin\Packages\Search;

use Flute\Core\Database\Entities\Module;
use Flute\Core\Database\Entities\Permission;
use Flute\Core\Database\Entities\Role;
use Flute\Core\Database\Entities\Server;
use Flute\Core\Database\Entities\SocialNetwork;
use Flute\Core\Database\Entities\User;

class SelectRegistry
{
    /**
     * @var array<string, array>
     * Example:
     *  [
     *    'servers' => [
     *      'class' => 'Flute\\Core\\Database\\Entities\\Server',
     *      'permission' => 'admin.servers',
     *      'searchFields' => ['name','ip','mod'],
     *      'displayField' => 'name',
     *      'valueField'   => 'id',
     *      'limit'        => 20,
     *    ],
     *    ...
     *  ]
     */
    private array $entities = [];

    public function __construct()
    {
        $this->entities = $this->getEntities();
    }

    /**
     * Register a new entity for select search.
     */
    public function registerEntity(string $alias, array $config): void
    {
        $this->entities[$alias] = $config;
    }

    /**
     * Get entity configuration (alias => config).
     */
    public function getEntityConfig(string $alias): ?array
    {
        return $this->entities[$alias] ?? null;
    }

    /**
     * Check if the user has permission to read this alias.
     */
    public function canUserAccessAlias(string $alias): bool
    {
        $config = $this->getEntityConfig($alias);
        if (!$config) {
            return false;
        }
        $perm = $config['permission'] ?? null;
        if (!$perm) {
            return false;
        }

        return user()->can($perm);
    }

    public function getEntities(): array
    {
        return [
            'servers' => [
                'class' => Server::class,
                'permission' => 'admin.servers',
                'searchFields' => ['name', 'ip', 'mod'],
                'displayField' => 'name',
                'valueField' => 'id',
                'limit' => 20,
            ],
            'users' => [
                'class' => User::class,
                'permission' => 'admin.users',
                'searchFields' => ['name', 'email'],
                'displayField' => 'name',
                'valueField' => 'id',
                'limit' => 20,
                'scope' => static function ($select) {
                    if (!user()->can('admin.boss')) {
                        $myPriority = user()->getHighestPriority();
                        $select->where('priority', '<', $myPriority);
                    }
                },
            ],
            'roles' => [
                'class' => Role::class,
                'permission' => 'admin.roles',
                'searchFields' => ['name'],
                'displayField' => 'name',
                'valueField' => 'id',
                'limit' => 20,
                'scope' => static function ($select) {
                    if (!user()->can('admin.boss')) {
                        $myPriority = user()->getHighestPriority();
                        $select->where('priority', '<', $myPriority);
                    }
                },
            ],
            'permissions' => [
                'class' => Permission::class,
                'permission' => 'admin.permissions',
                'searchFields' => ['name'],
                'displayField' => 'name',
                'valueField' => 'id',
                'limit' => 100,
            ],
            'modules' => [
                'class' => Module::class,
                'permission' => 'admin.modules',
                'searchFields' => ['name'],
                'displayField' => 'name',
                'valueField' => 'id',
                'limit' => 20,
            ],
            'socials' => [
                'class' => SocialNetwork::class,
                'permission' => 'admin.socials',
                'searchFields' => ['key'],
                'displayField' => 'key',
                'valueField' => 'id',
                'limit' => 20,
            ],
        ];
    }
}
