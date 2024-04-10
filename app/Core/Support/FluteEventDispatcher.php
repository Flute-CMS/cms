<?php

namespace Flute\Core\Support;

use Flute\Core\Contracts\CacheInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class FluteEventDispatcher extends EventDispatcher
{
    private $deferredListenersKey = 'flute.deferred_listeners';
    public $deferredListeners = [];

    public function __construct()
    {
        parent::__construct();
        $this->initializeDeferredListeners();
    }

    public function addDeferredListener($eventName, $listener, $priority = 0)
    {
        $listenerId = $this->getListenerId($listener);

        if (!isset($this->deferredListeners[$eventName])) {
            $this->deferredListeners[$eventName] = [];
        }

        $this->deferredListeners[$eventName][$listenerId] = ['listener' => $listener, 'priority' => $priority];

        if (class_exists($listener[0]))
            $this->addListener($eventName, $listener, $priority);
    }

    public function removeDeferredListener($eventName, $listener)
    {
        $listenerId = $this->getListenerId($listener);

        if (isset($this->deferredListeners[$eventName][$listenerId])) {
            unset($this->deferredListeners[$eventName][$listenerId]);

            if (empty($this->deferredListeners[$eventName])) {
                unset($this->deferredListeners[$eventName]);
            }

            cache()->set($this->deferredListenersKey, $this->deferredListeners);
        }

        $this->removeListener($eventName, $listener);
    }

    public function saveDeferredListenersToCache()
    {
        cache()->set($this->deferredListenersKey, $this->deferredListeners, 3600);
    }

    private function initializeDeferredListeners()
    {
        $deferredListeners = cache()->get($this->deferredListenersKey, []);

        foreach ($deferredListeners as $eventName => $listeners) {
            foreach ($listeners as $listenerData) {
                $this->addListener($eventName, $listenerData['listener'], $listenerData['priority']);
            }
        }

        $this->deferredListeners = $deferredListeners;
    }

    private function getListenerId($listener)
    {
        if (is_object($listener)) {
            return spl_object_hash($listener);
        }

        if (is_array($listener)) {
            return $listener[0] . '::' . $listener[1];
        }

        return $listener;
    }
}
