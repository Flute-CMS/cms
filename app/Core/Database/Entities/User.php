<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\Annotated\Annotation\Relation\ManyToMany;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\ORM\Relation\Pivoted\PivotedCollection;

/**
 * @Entity(
 *      repository="Flute\Core\Database\Repositories\UserRepository",
 * )
 * @Table(
 *      indexes={
 *          @Index(columns={"login"}, unique=true),
 *          @Index(columns={"uri"}, unique=true),
 *          @Index(columns={"email"}, unique=true)
 *      }
 * )
 */
class User
{
    protected const IS_ONLINE_TIME = 600;

    /** @Column(type="primary") */
    public $id;

    /** @Column(type="string", nullable=true) */
    public $login;

    /** @Column(type="string", nullable=true) */
    public $uri = null;

    /** @Column(type="string") */
    public $name;

    /** @Column(type="string", nullable=true) */
    public $avatar;

    /** @Column(type="string", nullable=true) */
    public $banner;

    /** @Column(type="string", nullable=true) */
    public $email;

    /** @Column(type="string", nullable=true) */
    public $password;

    /** @Column(type="boolean", default=false) */
    public $verified = false;

    /** @Column(type="boolean", default=false) */
    public $hidden = false;

    /** @Column(type = "decimal(10,2)") */
    public $balance = 0;

    /** @HasMany(target="UserSocialNetwork", cascade=true) */
    public $socialNetworks;

    /** @ManyToMany(target="Role", though="UserRole", cascade=true) */
    public $roles;

    /** @HasMany(target="RememberToken", cascade=true) */
    public $rememberTokens;

    /** @HasMany(target="UserDevice", cascade=true) */
    public $userDevices;

    /** @HasMany(target="UserBlock", cascade=true) */
    public $blocksGiven;

    /** @HasMany(target="UserBlock", cascade=true) */
    public $blocksReceived;

    /** @HasMany(target="UserActionLog", cascade=true) */
    public $actionLogs;

    /** @HasMany(target="PaymentInvoice", cascade=true) */
    public $invoices;

    /**
     * @Column(type="timestamp", default="CURRENT_TIMESTAMP")
     */
    public $created_at;

    /**
     * @Column(type="timestamp", nullable=true)
     */
    public $last_logged;

    public function __construct()
    {
        $this->roles = new PivotedCollection();
        $this->socialNetworks = new PivotedCollection();
        $this->rememberTokens = new PivotedCollection();
        $this->userDevices = new PivotedCollection();
        $this->blocksGiven = new PivotedCollection();
        $this->blocksReceived = new PivotedCollection();
        $this->created_at = new \DateTime();
        $this->last_logged = new \DateTime();
    }

    /**
     * Add a role to the user.
     *
     * @param Role $role The role to add.
     */
    public function addRole(Role $role): void
    {
        if (!$this->roles->contains($role)) {
            $this->roles->add($role);
        }
    }

    /**
     * Remove a role from the user.
     *
     * @param Role $role The role to remove.
     */
    public function removeRole(Role $role): void
    {
        if ($this->roles->contains($role)) {
            $this->roles->removeElement($role);
        }
    }

    public function clearRoles(): void
    {
        $this->roles->clear();
    }

    /**
     * Check if the user has a specific role.
     *
     * @param string $roleName The role name to check.
     * @return bool Returns true if the user has the role.
     */
    public function hasRole(string $roleName): bool
    {
        foreach ($this->roles as $role) {
            if ($role->name === $roleName) {
                return true;
            }
        }
        return false;
    }

    /**
     * Set the password for the user.
     *
     * @param string $password The password to set.
     */
    public function setPassword(string $password): void
    {
        $this->password = password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Serialize the user object to JSON.
     *
     * @return array The serialized user object.
     */
    public function jsonSerialize()
    {
        $user = get_object_vars($this);
        unset($user['password']);
        return $user;
    }

    /**
     * Check if the user has a specific permission.
     *
     * @param string $permissionName The permission name to check.
     * @return bool Returns true if the user has the permission.
     */
    public function hasPermission(string $permissionName): bool
    {
        foreach ($this->roles as $role) {
            foreach ($role->permissions as $permission) {
                if ($permission->name === $permissionName) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get the social network associated with the user.
     *
     * @param string $socialNetworkName The social network name.
     * @return mixed|null The social network entity or null if not found.
     */
    public function getSocialNetwork(string $socialNetworkName): ?UserSocialNetwork
    {
        foreach ($this->socialNetworks as $socialNetwork) {
            if ($socialNetwork->socialNetwork->key === $socialNetworkName) {
                return $socialNetwork;
            }
        }
        return null;
    }

    public function addSocialNetwork(SocialNetwork $socialNetwork): void
    {
        $this->socialNetworks->add($socialNetwork);
    }

    /**
     * Get all user devices associated with the user.
     */
    public function getUserDevices()
    {
        return $this->userDevices;
    }

    /**
     * Get all roles of the user.
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Get all permissions of the user.
     *
     * @return PivotedCollection The permissions of the user.
     */
    public function getPermissions()
    {
        $permissions = new PivotedCollection();

        /** @var Role $role */
        foreach ($this->roles as $role) {
            foreach ($role->permissions as $permission) {
                if (!$permissions->contains($permission)) {
                    $permissions->add($permission);
                }
            }
        }

        return $permissions;
    }

    public function getUrl()
    {
        return !empty($this->uri) ? $this->uri : $this->id;
    }

    /**
     * Check if the user is currently online.
     *
     * @return bool Returns true if the user was last logged in within the last 10 minutes.
     */
    public function isOnline(): bool
    {
        $now = new \DateTime();
        $lastLogged = $this->last_logged instanceof \DateTime ? $this->last_logged : new \DateTime($this->last_logged);
        $interval = $now->getTimestamp() - $lastLogged->getTimestamp();
        return $interval <= self::IS_ONLINE_TIME;
    }

    /**
     * Проверяет, заблокирован ли пользователь.
     *
     * @return bool Возвращает true, если пользователь заблокирован.
     */
    public function isBlocked(): bool
    {
        foreach ($this->blocksReceived as $block) {
            $now = new \DateTime();
            $blockedUntil = $block->blockedUntil ? $block->blockedUntil : null;

            if ($blockedUntil === null || $blockedUntil > $now) {
                return true;
            }
        }
        return false;
    }

    /**
     * Возвращает информацию о блокировке, если она существует.
     *
     * @return array|null Массив с информацией о блокировке или null, если блокировки нет.
     */
    public function getBlockInfo(): ?array
    {
        foreach ($this->blocksReceived as $block) {
            $now = new \DateTime();
            $blockedUntil = $block->blockedUntil ? $block->blockedUntil : null;

            if ($blockedUntil === null || $blockedUntil > $now) {
                return [
                    'reason' => $block->reason,
                    'blockedBy' => $block->blockedBy,
                    'blockedFrom' => $block->blockedFrom,
                    'blockedUntil' => $block->blockedUntil,
                ];
            }
        }
        return null;
    }
}