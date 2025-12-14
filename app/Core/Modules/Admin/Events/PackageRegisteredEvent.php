<?php

namespace Flute\Admin\Events;

use Flute\Admin\Contracts\AdminPackageInterface;
use Symfony\Contracts\EventDispatcher\Event;

class PackageRegisteredEvent extends Event
{
    public const NAME = 'admin.package_registered';

    protected AdminPackageInterface $package;

    public function __construct(AdminPackageInterface $package)
    {
        $this->package = $package;
    }

    public function getPackage(): AdminPackageInterface
    {
        return $this->package;
    }
}
