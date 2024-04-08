<?php

namespace Flute\Core\Support;

use Flute\Core\Contracts\CacheInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class FluteEventDispatcher extends EventDispatcher
{
    private $cache;
    private $deferredListenersKey = 'flute.deferred_listeners';

    public function __construct(CacheInterface $cache)
    {
        parent::__construct();
        $this->cache = $cache;
        $this->initializeDeferredListeners();
    }

    public function addDeferredListener($eventName, $listener, $priority = 0)
    {
        $deferredListeners = $this->cache->get($this->deferredListenersKey, function () {
            return [];
        });

        if (!is_array($deferredListeners)) {
            $deferredListeners = [];
        }


        $listenerId = $this->getListenerId($listener);

        if (!isset($deferredListeners[$eventName])) {
            $deferredListeners[$eventName] = [];
        }

        if (!isset($deferredListeners[$eventName][$listenerId])) {
            $deferredListeners[$eventName][$listenerId] = ['listener' => $listener, 'priority' => $priority];
            $this->cache->set($this->deferredListenersKey, $deferredListeners);
        }

        $this->addListener($eventName, $listener, $priority);
    }

    private function initializeDeferredListeners()
    {
        $deferredListeners = $this->cache->get($this->deferredListenersKey, function () {
            return [];
        });

        foreach ($deferredListeners as $eventName => $listeners) {
            foreach ($listeners as $listenerData) {
                $this->addListener($eventName, $listenerData['listener'], $listenerData['priority']);
            }
        }
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
