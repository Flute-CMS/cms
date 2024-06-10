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
        $this->cleanDuplicates(); // Очистка дубликатов при инициализации
    }

    public function addDeferredListener($eventName, $listener, $priority = 0)
    {
        $listenerId = $this->getListenerId($listener);

        if (!isset($this->deferredListeners[$eventName])) {
            $this->deferredListeners[$eventName] = [];
        }

        // Check if listener with the same content already exists
        foreach ($this->deferredListeners[$eventName] as $existingListenerId => $existingListenerData) {
            if ($existingListenerId !== $listenerId && $this->compareListeners($listener, $existingListenerData['listener'])) {
                return;
            }
        }

        if (isset($this->deferredListeners[$eventName][$listenerId]))
            return;

        $this->deferredListeners[$eventName][$listenerId] = ['listener' => $listener, 'priority' => $priority];

        if (is_callable($listener)) {
            $this->addListener($eventName, $listener, $priority);
            $this->saveDeferredListenersToCache();
        }

        $this->cleanDuplicates();
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
        $this->cleanDuplicates(); // Очистка дубликатов после удаления слушателя
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
        $this->cleanDuplicates(); // Очистка дубликатов при инициализации
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
                return get_class($listener[0]) . '::' . $listener[1];
            }

            return $listener[0] . '::' . $listener[1];
        }

        if (is_object($listener)) {
            return get_class($listener);
        }

        return $listener;
    }

    private function getClosureId($closure)
    {
        $reflection = new ReflectionClosure($closure);
        $code = $reflection->getCode();

        return md5($code);
    }

    private function compareListeners($listener1, $listener2)
    {
        if (is_array($listener1) && is_array($listener2) && is_object($listener1[0]) && is_object($listener2[0])) {
            return get_class($listener1[0]) === get_class($listener2[0]);
        }

        if ($listener1 === $listener2) {
            return true;
        }

        if ($listener1 instanceof \Closure && $listener2 instanceof \Closure) {
            return $this->getClosureId($listener1) === $this->getClosureId($listener2);
        }

        return false;
    }

    private function cleanDuplicates()
    {
        foreach ($this->deferredListeners as $eventName => $listeners) {
            $uniqueListeners = [];

            foreach ($listeners as $listenerId => $listenerData) {
                foreach ($uniqueListeners as $uniqueListenerData) {
                    if ($this->compareListeners($listenerData['listener'], $uniqueListenerData['listener'])) {
                        unset($this->deferredListeners[$eventName][$listenerId]);
                        break;
                    }
                }
                $uniqueListeners[$listenerId] = $listenerData;
            }
        }

        $this->saveDeferredListenersToCache();
    }
}
