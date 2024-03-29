<?php

namespace Flute\Core\Auth;

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
        return session()->get($key);
    }

    /**
     * Add or Update an item to storage
     *
     * @param string $key
     * @param string $value
     */
    public function set($key, $value)
    {
        session()->set($key, $value);
        $key = strtolower($key);

        if (is_object($value)) {
            $value = ['lateObject' => serialize($value)];
        }

        session()->set($key, $value);
    }

    /**
     * Delete an item from storage
     *
     * @param string $key
     */
    public function delete($key)
    {
        session()->remove($key);
    }

    /**
     * Delete a item from storage
     *
     * @param string $key
     */
    public function deleteMatch($key)
    {
        session()->remove($key);
    }

    /**
     * Clear all items in storage
     */
    public function clear()
    {
        session()->clear();
    }
}