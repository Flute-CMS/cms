<?php

namespace Flute\Admin\Packages\Marketplace\Services\Concerns;

use Exception;

trait HandlesMarketplaceLock
{
    /**
     * @var resource|null
     */
    private $marketplaceLockHandle = null;

    protected function acquireMarketplaceLock(): void
    {
        if ($this->marketplaceLockHandle !== null) {
            return;
        }

        $lockPath = $this->tempDir . '/.marketplace-install.lock';
        $handle = @fopen($lockPath, 'cb');
        if ($handle === false) {
            throw new Exception(__('admin-marketplace.messages.install_failed') . ': lock file');
        }

        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            throw new Exception(__('admin-marketplace.messages.concurrent_marketplace_operation'));
        }

        $this->marketplaceLockHandle = $handle;
    }

    protected function releaseMarketplaceLock(): void
    {
        if ($this->marketplaceLockHandle === null) {
            return;
        }

        flock($this->marketplaceLockHandle, LOCK_UN);
        fclose($this->marketplaceLockHandle);
        $this->marketplaceLockHandle = null;
    }
}
