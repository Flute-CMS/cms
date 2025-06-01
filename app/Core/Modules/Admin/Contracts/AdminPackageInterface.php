<?php

namespace Flute\Admin\Contracts;

/**
 * Interface AdminPackageInterface
 *
 * Defines the contract for admin packages.
 */
interface AdminPackageInterface
{
    /**
     * Initialize the package.
     *
     * This method is used to perform any setup or initialization required by the package.
     *
     * @return void
     */
    public function initialize(): void;

    /**
     * Get the permissions required by the package.
     *
     * Returns an array of permission strings that are required to access the package's functionalities.
     *
     * @return array
     */
    public function getPermissions(): array;

    /**
     * Get the menu items for the admin panel.
     *
     * Returns an array of menu items to be displayed in the admin navigation.
     *
     * @return array
     */
    public function getMenuItems(): array;

    /**
     * Boot the package.
     *
     * This method is called after all packages have been initialized and allows the package to perform actions that depend on other services.
     *
     * @return void
     */
    public function boot(): void;

    public function getPriority(): int;
    public function getBasePath(): string;
}
