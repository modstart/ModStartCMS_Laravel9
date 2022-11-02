<?php

namespace Sabre\Event;

use Exception;


class Promise {

    
    const PENDING = 0;

    
    const FULFILLED = 1;

    
    const REJECTED = 2;

    
    public $state = self::PENDING;

    
    function __construct(callable $executor = null) {

        if ($executor) {
            $executor(
                [$this, 'fulfill'],
                [$this, 'reject']
            );
        }

    }

    
    function then(callable $onFulfilled = null, callable $onRejected = null) {

                                $subPromise = new self();

        switch ($this->state) {
            case self::PENDING :
                                                $this->subscribers[] = [$subPromise, $onFulfilled, $onRejected];
                break;
            case self::FULFILLED :
                                                $this->invokeCallback($subPromise, $onFulfilled);
                break;
            case self::REJECTED :
                                                $this->invokeCallback($subPromise, $onRejected);
                break;
        }
        return $subPromise;

    }

    
    function otherwise(callable $onRejected) {

        return $this->then(null, $onRejected);

    }

    
    function fulfill($value = null) {
        if ($this->state !== self::PENDING) {
            throw new PromiseAlreadyResolvedException('This promise is already resolved, and you\'re not allowed to resolve a promise more than once');
        }
        $this->state = self::FULFILLED;
        $this->value = $value;
        foreach ($this->subscribers as $subscriber) {
            $this->invokeCallback($subscriber[0], $subscriber[1]);
        }
    }

    
    function reject($reason = null) {
        if ($this->state !== self::PENDING) {
            throw new PromiseAlreadyResolvedException('This promise is already resolved, and you\'re not allowed to resolve a promise more than once');
        }
        $this->state = self::REJECTED;
        $this->value = $reason;
        foreach ($this->subscribers as $subscriber) {
            $this->invokeCallback($subscriber[0], $subscriber[2]);
        }

    }

    
    function wait() {

        $hasEvents = true;
        while ($this->state === self::PENDING) {

            if (!$hasEvents) {
                throw new \LogicException('There were no more events in the loop. This promise will never be fulfilled.');
            }

                                    $hasEvents = Loop\tick(true);

        }

        if ($this->state === self::FULFILLED) {
                        return $this->value;
        } else {
                                    $reason = $this->value;
            if ($reason instanceof Exception) {
                throw $reason;
            } elseif (is_scalar($reason)) {
                throw new Exception($reason);
            } else {
                $type = is_object($reason) ? get_class($reason) : gettype($reason);
                throw new Exception('Promise was rejected with reason of type: ' . $type);
            }
        }


    }


    
    protected $subscribers = [];

    
    protected $value = null;

    
    private function invokeCallback(Promise $subPromise, callable $callBack = null) {

                                                Loop\nextTick(function() use ($callBack, $subPromise) {
            if (is_callable($callBack)) {
                try {

                    $result = $callBack($this->value);
                    if ($result instanceof self) {
                                                                                                                        $result->then([$subPromise, 'fulfill'], [$subPromise, 'reject']);
                    } else {
                                                                        $subPromise->fulfill($result);
                    }
                } catch (Exception $e) {
                                                            $subPromise->reject($e);
                }
            } else {
                if ($this->state === self::FULFILLED) {
                    $subPromise->fulfill($this->value);
                } else {
                    $subPromise->reject($this->value);
                }
            }
        });
    }

    
    function error(callable $onRejected) {

        return $this->otherwise($onRejected);

    }

    
    static function all(array $promises) {

        return Promise\all($promises);

    }

}
