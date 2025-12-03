<?php

namespace Flute\Core\Support;

use Closure;
use Laravel\SerializableClosure\SerializableClosure;
use Laravel\SerializableClosure\Support\ReflectionClosure;
use Symfony\Component\EventDispatcher\EventDispatcher;

class FluteEventDispatcher extends EventDispatcher
{
    public $deferredListeners = [];

    private $deferredListenersKey = 'flute.deferred_listeners';

    private bool $isDirty = false;

    private static array $closureIdCache = [];

    public function __construct()
    {
        parent::__construct();
        $this->initializeDeferredListeners();
    }

    public function addDeferredSubscriber($subscriber)
    {
        $events = $subscriber->getSubscribedEvents();

        foreach ($events as $eventName => $listener) {
            $this->addDeferredListener($eventName, [$subscriber, $listener]);
        }
    }

    public function addDeferredListener($eventName, $listener, $priority = 0)
    {
        $listenerId = $this->getListenerId($listener);

        if (!isset($this->deferredListeners[$eventName])) {
            $this->deferredListeners[$eventName] = [];
        }

        if (isset($this->deferredListeners[$eventName][$listenerId])) {
            return;
        }

        $this->deferredListeners[$eventName][$listenerId] = ['listener' => $listener, 'priority' => $priority];
        $this->isDirty = true;

        if (is_callable($listener)) {
            $this->addListener($eventName, $listener, $priority);
        }
    }

    public function removeDeferredListener($eventName, $listener)
    {
        $listenerId = $this->getListenerId($listener);

        if (isset($this->deferredListeners[$eventName][$listenerId])) {
            unset($this->deferredListeners[$eventName][$listenerId]);

            if (empty($this->deferredListeners[$eventName])) {
                unset($this->deferredListeners[$eventName]);
            }

            $this->isDirty = true;
        }

        $this->removeListener($eventName, $listener);
    }

    public function saveDeferredListenersToCache()
    {
        if (!$this->isDirty) {
            return;
        }

        cache()->set($this->deferredListenersKey, $this->deferredListeners, 3600);
        $this->isDirty = false;
    }

    private function initializeDeferredListeners()
    {
        $deferredListeners = cache()->get($this->deferredListenersKey, []);

        if (!is_array($deferredListeners)) {
            $deferredListeners = [];
        }

        foreach ($deferredListeners as $eventName => $listeners) {
            foreach ($listeners as $listenerData) {
                $listener = $listenerData['listener'];
                if ($listener instanceof SerializableClosure) {
                    $listener = $listener->getClosure();
                }

                if (is_callable($listener)) {
                    $this->addListener($eventName, $listener, $listenerData['priority']);
                }
            }
        }

        $this->deferredListeners = $deferredListeners;
    }

    private function getListenerId($listener)
    {
        if ($listener instanceof Closure) {
            return $this->getClosureId($listener);
        }

        if ($listener instanceof SerializableClosure) {
            return $this->getClosureId($listener->getClosure());
        }

        if (is_array($listener)) {
            if (is_object($listener[0])) {
                return get_class($listener[0]) . '::' . $listener[1];
            }

            return $listener[0] . '::' . $listener[1];
        }

        if (is_object($listener)) {
            return $listener::class;
        }

        return $listener;
    }

    private function getClosureId($closure)
    {
        $objectId = spl_object_id($closure);

        if (isset(self::$closureIdCache[$objectId])) {
            return self::$closureIdCache[$objectId];
        }

        $reflection = new ReflectionClosure($closure);
        $code = $reflection->getCode();
        $id = md5($code);

        self::$closureIdCache[$objectId] = $id;

        return $id;
    }
}
