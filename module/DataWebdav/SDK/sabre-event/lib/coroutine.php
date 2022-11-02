<?php

namespace Sabre\Event;

use Generator;
use Exception;


function coroutine(callable $gen) {

    $generator = $gen();
    if (!$generator instanceof Generator) {
        throw new \InvalidArgumentException('You must pass a generator function');
    }

        $promise = new Promise();

    $lastYieldResult = null;

    
    $advanceGenerator = function() use (&$advanceGenerator, $generator, $promise, &$lastYieldResult) {

        while ($generator->valid()) {

            $yieldedValue = $generator->current();
            if ($yieldedValue instanceof Promise) {
                $yieldedValue->then(
                    function($value) use ($generator, &$advanceGenerator, &$lastYieldResult) {
                        $lastYieldResult = $value;
                        $generator->send($value);
                        $advanceGenerator();
                    },
                    function($reason) use ($generator, $advanceGenerator) {
                        if ($reason instanceof Exception) {
                            $generator->throw($reason);
                        } elseif (is_scalar($reason)) {
                            $generator->throw(new Exception($reason));
                        } else {
                            $type = is_object($reason) ? get_class($reason) : gettype($reason);
                            $generator->throw(new Exception('Promise was rejected with reason of type: ' . $type));
                        }
                        $advanceGenerator();
                    }
                )->error(function($reason) use ($promise) {
                                                                                $promise->reject($reason);
                });
                                                break;
            } else {
                                $lastYieldResult = $yieldedValue;
                $generator->send($yieldedValue);
            }

        }

                                if (!$generator->valid() && $promise->state === Promise::PENDING) {
            $promise->fulfill($lastYieldResult);
        }

    };

    try {
        $advanceGenerator();
    } catch (Exception $e) {
        $promise->reject($e);
    }

    return $promise;

}
