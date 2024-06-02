<?php
namespace Flute\Core\Support;

use Laravel\SerializableClosure\SerializableClosure;
use Laravel\SerializableClosure\Support\ReflectionClosure;
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

        // if ($listener instanceof \Closure) {
        //     $listener = new SerializableClosure($listener);
        // }

        if (!isset($this->deferredListeners[$eventName])) {
            $this->deferredListeners[$eventName] = [];
        }

        if (isset($this->deferredListeners[$eventName][$listenerId]))
            return;

        $this->deferredListeners[$eventName][$listenerId] = ['listener' => $listener, 'priority' => $priority];

        if (is_callable($listener)) {
            $this->addListener($eventName, $listener, $priority);
            $this->saveDeferredListenersToCache();
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

            $this->saveDeferredListenersToCache();
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
        if ($listener instanceof \Closure) {
            return $this->getClosureId($listener);
        }

        if ($listener instanceof SerializableClosure) {
            return $this->getClosureId($listener->getClosure());
        }

        if (is_array($listener)) {
            if (is_object($listener[0])) {
                return spl_object_hash($listener[0]) . '::' . $listener[1];
            }

            return $listener[0] . '::' . $listener[1];
        }

        if (is_object($listener)) {
            return $this->getClosureId($listener);
        }

        return $listener;
    }

    private function getClosureId($closure)
    {
        $reflection = new ReflectionClosure($closure);
        $code = $reflection->getCode();

        return md5($code);
    }
}
