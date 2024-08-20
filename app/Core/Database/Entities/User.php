<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\Annotated\Annotation\Relation\ManyToMany;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Table\Index;

#[Entity(repository: "Flute\Core\Database\Repositories\UserRepository")]
#[Table(
    indexes: [
        new Index(columns: ["login"], unique: true),
        new Index(columns: ["uri"], unique: true),
        new Index(columns: ["email"], unique: true)
    ]
)]
class User
{
    protected const IS_ONLINE_TIME = 600;

    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string", nullable: true)]
    public ?string $login = null;

    #[Column(type: "string", nullable: true)]
    public ?string $uri = null;

    #[Column(type: "string")]
    public string $name;

    #[Column(type: "string", nullable: true)]
    public ?string $avatar = null;

    #[Column(type: "string", nullable: true)]
    public ?string $banner = null;

    #[Column(type: "string", nullable: true)]
    public ?string $email = null;

    #[Column(type: "string", nullable: true)]
    public ?string $password = null;

    #[Column(type: "boolean", default: false)]
    public bool $verified = false;

    #[Column(type: "boolean", default: false)]
    public bool $hidden = false;

    #[Column(type: "decimal(10,2)")]
    public float $balance = 0;

    #[HasMany(target: "UserSocialNetwork", cascade: true)]
    public array $socialNetworks = [];

    #[ManyToMany(target: "Role", through: "UserRole", cascade: true)]
    public array $roles = [];

    #[HasMany(target: "RememberToken", cascade: true)]
    public array $rememberTokens = [];

    #[HasMany(target: "UserDevice", cascade: true)]
    public array $userDevices = [];

    #[HasMany(target: "UserBlock", cascade: true)]
    public array $blocksGiven = [];

    #[HasMany(target: "UserBlock", cascade: true)]
    public array $blocksReceived = [];

    #[HasMany(target: "UserActionLog", cascade: true)]
    public array $actionLogs = [];

    #[HasMany(target: "PaymentInvoice", cascade: true)]
    public array $invoices = [];

    #[Column(type: "timestamp", default: "CURRENT_TIMESTAMP")]
    public \DateTimeImmutable $created_at;

    #[Column(type: "timestamp", nullable: true)]
    public ?\DateTimeImmutable $last_logged = null;

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
        $this->last_logged = new \DateTimeImmutable();
    }

    public function addRole(Role $role): void
    {
        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }
    }

    public function removeRole(Role $role): void
    {
        $this->roles = array_filter(
            $this->roles,
            fn($r) => $r !== $role
        );
    }

    public function clearRoles(): void
    {
        $this->roles = [];
    }

    public function hasRole(string $roleName): bool
    {
        foreach ($this->roles as $role) {
            if ($role->name === $roleName) {
                return true;
            }
        }
        return false;
    }

    public function setPassword(string $password): void
    {
        $this->password = password_hash($password, PASSWORD_BCRYPT);
    }

    public function jsonSerialize(): array
    {
        $user = get_object_vars($this);
        unset($user['password']);
        return $user;
    }

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

    public function getPermissions()
    {
        $permissions = [];

        foreach ($this->roles as $role) {
            foreach ($role->permissions as $permission) {
                if (!in_array($permission, $permissions)) {
                    $permissions[] = $permission;
                }
            }
        }

        return $permissions;
    }

    public function getSocialNetwork(string $socialNetworkName): ?UserSocialNetwork
    {
        foreach ($this->socialNetworks as $socialNetwork) {
            if ($socialNetwork->socialNetwork->key === $socialNetworkName) {
                return $socialNetwork;
            }
        }
        return null;
    }

    public function hasSocialNetwork(string $socialNetworkName): bool
    {
        foreach ($this->socialNetworks as $socialNetwork) {
            if ($socialNetwork->socialNetwork->key === $socialNetworkName) {
                return true;
            }
        }
        return false;
    }

    public function addSocialNetwork(SocialNetwork $socialNetwork): void
    {
        $this->socialNetworks[] = $socialNetwork;
    }

    public function getUrl(): string
    {
        return !empty($this->uri) ? $this->uri : (string) $this->id;
    }

    public function isOnline(): bool
    {
        $now = new \DateTime();
        $lastLogged = $this->last_logged instanceof \DateTime ? $this->last_logged : new \DateTime($this->last_logged);
        $interval = $now->getTimestamp() - $lastLogged->getTimestamp();
        return $interval <= self::IS_ONLINE_TIME;
    }

    public function isBlocked(): bool
    {
        foreach ($this->blocksReceived as $block) {
            $now = new \DateTime();
            $blockedUntil = $block->blockedUntil;

            if ($blockedUntil === null || $blockedUntil > $now) {
                return true;
            }
        }
        return false;
    }

    public function getBlockInfo(): ?array
    {
        foreach ($this->blocksReceived as $block) {
            $now = new \DateTime();
            $blockedUntil = $block->blockedUntil;

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
