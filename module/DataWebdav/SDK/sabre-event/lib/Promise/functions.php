<?php

namespace Sabre\Event\Promise;

use Sabre\Event\Promise;





function all(array $promises) {

    return new Promise(function($success, $fail) use ($promises) {

        $successCount = 0;
        $completeResult = [];

        foreach ($promises as $promiseIndex => $subPromise) {

            $subPromise->then(
                function($result) use ($promiseIndex, &$completeResult, &$successCount, $success, $promises) {
                    $completeResult[$promiseIndex] = $result;
                    $successCount++;
                    if ($successCount === count($promises)) {
                        $success($completeResult);
                    }
                    return $result;
                }
            )->error(
                function($reason) use ($fail) {
                    $fail($reason);
                }
            );

        }
    });

}


function race(array $promises) {

    return new Promise(function($success, $fail) use ($promises) {

        $alreadyDone = false;
        foreach ($promises as $promise) {

            $promise->then(
                function($result) use ($success, &$alreadyDone) {
                    if ($alreadyDone) {
                        return;
                    }
                    $alreadyDone = true;
                    $success($result);
                },
                function($reason) use ($fail, &$alreadyDone) {
                    if ($alreadyDone) {
                        return;
                    }
                    $alreadyDone = true;
                    $fail($reason);
                }
            );

        }

    });

}



function resolve($value) {

    if ($value instanceof Promise) {
        return $value->then();
    } else {
        $promise = new Promise();
        $promise->fulfill($value);
        return $promise;
    }

}


function reject($reason) {

    $promise = new Promise();
    $promise->reject($reason);
    return $promise;

}
