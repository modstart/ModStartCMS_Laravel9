<?php

namespace Sabre\Event;


interface EventEmitterInterface {

    
    function on($eventName, callable $callBack, $priority = 100);

    
    function once($eventName, callable $callBack, $priority = 100);

    
    function emit($eventName, array $arguments = [], callable $continueCallBack = null);

    
    function listeners($eventName);

    
    function removeListener($eventName, callable $listener);

    
    function removeAllListeners($eventName = null);

}
