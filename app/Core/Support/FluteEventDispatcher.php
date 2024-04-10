<?php

namespace Flute\Core\Support;

use Flute\Core\Contracts\CacheInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class FluteEventDispatcher extends EventDispatcher
{
    private $cache;
    private $deferredListenersKey = 'flute.deferred_listeners';
    private $deferredListeners = [];

    public function __construct(CacheInterface $cache)
    {
        parent::__construct();
        $this->cache = $cache;
        $this->initializeDeferredListeners();
    }

    public function addDeferredListener($eventName, $listener, $priority = 0)
    {
        $listenerId = $this->getListenerId($listener);

        if (!isset($this->deferredListeners[$eventName])) {
            $this->deferredListeners[$eventName] = [];
        }

        $this->deferredListeners[$eventName][$listenerId] = ['listener' => $listener, 'priority' => $priority];

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

            $this->cache->set($this->deferredListenersKey, $this->deferredListeners);
        }

        $this->removeListener($eventName, $listener);
    }

    public function saveDeferredListenersToCache()
    {
        $this->cache->set($this->deferredListenersKey, $this->deferredListeners);
    }

    private function initializeDeferredListeners()
    {
        $deferredListeners = $this->cache->get($this->deferredListenersKey, []);

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
