<?php

namespace Flute\Core\Modules\Auth\Hybrid\Storage;

use Hybridauth\Storage\StorageInterface;

class StorageSession implements StorageInterface
{
    /**
     * Retrieve a item from storage
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        $storage = session()->get('HYBRIDAUTH::STORAGE', []);

        $value = $storage[$key] ?? null;

        if (is_array($value) && isset($value['__lateObject'])) {
            try {
                return unserialize($value['__lateObject']);
            } catch (\Throwable $e) {
                logs()->warning('Failed to unserialize Hybridauth stored object: ' . $e->getMessage());

                return $value;
            }
        }

        return $value;
    }

    /**
     * Add or Update an item to storage
     *
     * @param string $key
     * @param string $value
     */
    public function set($key, $value)
    {
        $storage = session()->get('HYBRIDAUTH::STORAGE', []);

        if (is_object($value)) {
            $value = ['__lateObject' => serialize($value)];
        }

        $storage[$key] = $value;

        session()->set('HYBRIDAUTH::STORAGE', $storage);
    }

    /**
     * Delete an item from storage
     *
     * @param string $key
     */
    public function delete($key = null): void
    {
        if ($key === null) {
            session()->remove('HYBRIDAUTH::STORAGE');

            return;
        }

        $storage = session()->get('HYBRIDAUTH::STORAGE', []);

        if (isset($storage[$key])) {
            unset($storage[$key]);
            session()->set('HYBRIDAUTH::STORAGE', $storage);
        }
    }

    /**
     * Delete a item from storage
     *
     * @param string $key
     */
    public function deleteMatch($key)
    {
        $storage = session()->get('HYBRIDAUTH::STORAGE', []);

        foreach (array_keys($storage) as $k) {
            if (strpos($k, $key) !== false) {
                unset($storage[$k]);
            }
        }

        session()->set('HYBRIDAUTH::STORAGE', $storage);
    }

    /**
     * Clear all items in storage
     */
    public function clear()
    {
        session()->remove('HYBRIDAUTH::STORAGE');
    }
}
