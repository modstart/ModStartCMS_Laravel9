<?php

namespace Sabre\Event;


trait EventEmitterTrait {

    
    protected $listeners = [];

    
    function on($eventName, callable $callBack, $priority = 100) {

        if (!isset($this->listeners[$eventName])) {
            $this->listeners[$eventName] = [
                true,                  [$priority],
                [$callBack]
            ];
        } else {
            $this->listeners[$eventName][0] = false;             $this->listeners[$eventName][1][] = $priority;
            $this->listeners[$eventName][2][] = $callBack;
        }

    }

    
    function once($eventName, callable $callBack, $priority = 100) {

        $wrapper = null;
        $wrapper = function() use ($eventName, $callBack, &$wrapper) {

            $this->removeListener($eventName, $wrapper);
            return call_user_func_array($callBack, func_get_args());

        };

        $this->on($eventName, $wrapper, $priority);

    }

    
    function emit($eventName, array $arguments = [], callable $continueCallBack = null) {

        if (is_null($continueCallBack)) {

            foreach ($this->listeners($eventName) as $listener) {

                $result = call_user_func_array($listener, $arguments);
                if ($result === false) {
                    return false;
                }
            }

        } else {

            $listeners = $this->listeners($eventName);
            $counter = count($listeners);

            foreach ($listeners as $listener) {

                $counter--;
                $result = call_user_func_array($listener, $arguments);
                if ($result === false) {
                    return false;
                }

                if ($counter > 0) {
                    if (!$continueCallBack()) break;
                }

            }

        }

        return true;

    }

    
    function listeners($eventName) {

        if (!isset($this->listeners[$eventName])) {
            return [];
        }

                if (!$this->listeners[$eventName][0]) {

                        array_multisort($this->listeners[$eventName][1], SORT_NUMERIC, $this->listeners[$eventName][2]);

                        $this->listeners[$eventName][0] = true;
        }

        return $this->listeners[$eventName][2];

    }

    
    function removeListener($eventName, callable $listener) {

        if (!isset($this->listeners[$eventName])) {
            return false;
        }
        foreach ($this->listeners[$eventName][2] as $index => $check) {
            if ($check === $listener) {
                unset($this->listeners[$eventName][1][$index]);
                unset($this->listeners[$eventName][2][$index]);
                return true;
            }
        }
        return false;

    }

    
    function removeAllListeners($eventName = null) {

        if (!is_null($eventName)) {
            unset($this->listeners[$eventName]);
        } else {
            $this->listeners = [];
        }

    }

}
