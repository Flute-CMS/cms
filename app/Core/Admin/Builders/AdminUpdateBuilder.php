<?php

namespace Flute\Core\Admin\Builders;

use Flute\Core\Admin\AdminBuilder;
use Flute\Core\Admin\Contracts\AdminBuilderInterface;
use Flute\Core\App;
use Flute\Core\Template\Template;

class AdminUpdateBuilder implements AdminBuilderInterface
{
    public const CACHE_KEY = 'flute.update_check';
    public const ORG_KEY = 'Flute-CMS';
    public const REP_KEY = 'cms';
    protected bool $needToUpdate = false;

    public function build(AdminBuilder $adminBuilder): void
    {
        $this->checkUpdates();
    }

    public function latestVersion(): string
    {
        return git(self::ORG_KEY, self::REP_KEY)->getLatestVersion();
    }

    public function latestChanges(): string
    {
        return git(self::ORG_KEY, self::REP_KEY)->getLatestRelease()['body'];
    }

    public function needUpdate(): bool
    {
        return $this->needToUpdate;
    }

    protected function checkUpdates()
    {
        $this->needToUpdate = version_compare(App::VERSION, $this->latestVersion(), '<');
    }
}