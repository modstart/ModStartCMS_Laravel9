#!/usr/bin/env php
<?php

use Sabre\Event\Promise;
use Sabre\Event\Loop;
use function Sabre\Event\coroutine;

require __DIR__ . '/../vendor/autoload.php';





$promise = new Promise();


Loop\setTimeout(function() use ($promise) {

    echo "Step 1\n";
    $promise->fulfill("hello");

}, 2);




$result = $promise
    ->then(function($value) {

        echo "Step 2\n";
                return $value . " world";

    })
    ->then(function($value) {

        echo "Step 3\n";
                $promise = new Promise();

                Loop\setTimeout(function() use ($promise, $value) {

            $promise->fulfill($value . ", how are ya?");

        }, 2);

        return $promise;
    })
    ->then(function($value) {

        echo "Step 4\n";
                return $value . " you rock!";
         
    })
        ->wait();

echo $result, "\n";



$result = coroutine(function() {

    $promise = new Promise();

    
    Loop\setTimeout(function() use ($promise) {

        echo "Step 1\n";
        $promise->fulfill("hello");

    }, 2);

    $value = (yield $promise);

    echo "Step 2\n";
    $value .= ' world';

    echo "Step 3\n";
    $promise = new Promise();
    Loop\setTimeout(function() use ($promise, $value) {

        $promise->fulfill($value . ", how are ya?");

    }, 2);

    $value = (yield $promise);

    echo "Step 4\n";

        yield $value . " you rock!";

})->wait();

echo $result, "\n";
