<?php

namespace Flute\Core\Services;

use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

class FlashService implements FlashBagInterface
{
    private FlashBagInterface $flashBag;

    const
        SUCCESS_TYPE = "success",
        ERROR_TYPE = "error",
        WARNING_TYPE = "warning",
        INFO_TYPE = "info";

    /**
     * FlashService constructor.
     *
     * @param SessionService $session The session service
     */
    public function __construct(SessionService $session)
    {
        $this->flashBag = $session->getFlashBag();
    }

    /**
     * Adds a flash message for the given type.
     *
     * @param string $type    Message type
     * @param mixed  $message The flash message
     */
    public function add(string $type, $message)
    {
        $this->flashBag->add($type, $message);
    }

    /**
     * Adds a success flash message.
     *
     * @param mixed  $message The flash message
     */
    public function success($message)
    {
        $this->flashBag->add(self::SUCCESS_TYPE, $message);
    }

    /**
     * Adds a error flash message.
     *
     * @param mixed  $message The flash message
     */
    public function error($message)
    {
        $this->flashBag->add(self::ERROR_TYPE, $message);
    }

    /**
     * Adds a warning flash message.
     *
     * @param mixed  $message The flash message
     */
    public function warning($message)
    {
        $this->flashBag->add(self::WARNING_TYPE, $message);
    }

    /**
     * Adds a info flash message.
     *
     * @param mixed  $message The flash message
     */
    public function info($message)
    {
        $this->flashBag->add(self::INFO_TYPE, $message);
    }

    /**
     * Registers one or more messages for a given type.
     *
     * @param string       $type     Message type
     * @param string|array $messages The flash messages
     */
    public function set(string $type, $messages)
    {
        $this->flashBag->set($type, $messages);
    }

    /**
     * Gets flash messages for a given type.
     *
     * @param string $type    Message type
     * @param array  $default Default value if $type does not exist
     *
     * @return array The flash messages
     */
    public function peek(string $type, array $default = []) : array
    {
        return $this->flashBag->peek($type, $default);
    }

    /**
     * Gets all flash messages.
     *
     * @return array All flash messages
     */
    public function peekAll() : array
    {
        return $this->flashBag->peekAll();
    }

    /**
     * Gets and clears flash messages for a given type.
     *
     * @param string $type    Message type
     * @param array  $default Default value if $type does not exist
     *
     * @return array The flash messages
     */
    public function get(string $type, array $default = []) : array
    {
        return $this->flashBag->get($type, $default);
    }

    /**
     * Gets and clears all flash messages.
     *
     * @return array All flash messages
     */
    public function all() : array
    {
        return $this->flashBag->all();
    }

    /**
     * Sets all flash messages.
     *
     * @param array $messages The flash messages
     */
    public function setAll(array $messages)
    {
        $this->flashBag->setAll($messages);
    }

    /**
     * Checks if flash messages exist for a given type.
     *
     * @param string $type Message type
     *
     * @return bool True if flash messages exist, false otherwise
     */
    public function has(string $type) : bool
    {
        return $this->flashBag->has($type);
    }

    /**
     * Returns a list of all defined flash message types.
     *
     * @return array A list of flash message types
     */
    public function keys() : array
    {
        return $this->flashBag->keys();
    }

    /**
     * Returns the name of the flash bag.
     *
     * @return string The flash bag name
     */
    public function getName() : string
    {
        return $this->flashBag->getName();
    }

    /**
     * Initializes the flash bag.
     *
     * @param array $array The flash bag array
     */
    public function initialize(array &$array)
    {
        $this->flashBag->initialize($array);
    }

    /**
     * Returns the storage key of the flash bag.
     *
     * @return string The flash bag storage key
     */
    public function getStorageKey() : string
    {
        return $this->flashBag->getStorageKey();
    }

    /**
     * Clears all flash messages.
     */
    public function clear() : mixed
    {
        return $this->flashBag->clear();
    }
}
