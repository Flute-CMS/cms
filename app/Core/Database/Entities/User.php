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
    /** @Column(type="primary") */
    public $id;

    /** @Column(type="string", nullable=true) */
    public $login;

    /** @Column(type="string", nullable=true) */
    public $uri;

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

    public function __construct()
    {
        $this->roles = new PivotedCollection();
        $this->socialNetworks = new PivotedCollection();
        $this->rememberTokens = new PivotedCollection();
        $this->userDevices = new PivotedCollection();
        $this->created_at = new \DateTime();
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
    public function getSocialNetwork(string $socialNetworkName)
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
}