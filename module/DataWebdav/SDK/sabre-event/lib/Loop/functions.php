<?php

namespace Sabre\Event\Loop;


function setTimeout(callable $cb, $timeout) {

    instance()->setTimeout($cb, $timeout);

}


function setInterval(callable $cb, $timeout) {

    return instance()->setInterval($cb, $timeout);

}


function clearInterval($intervalId) {

    instance()->clearInterval($intervalId);

}


function nextTick(callable $cb) {

    instance()->nextTick($cb);

}



function addReadStream($stream, callable $cb) {

    instance()->addReadStream($stream, $cb);

}


function addWriteStream($stream, callable $cb) {

    instance()->addWriteStream($stream, $cb);

}


function removeReadStream($stream) {

    instance()->removeReadStream($stream);

}


function removeWriteStream($stream) {

    instance()->removeWriteStream($stream);

}



function run() {

    instance()->run();

}


function tick($block = false) {

    return instance()->tick($block);

}


function stop() {

    instance()->stop();

}


function instance(Loop $newLoop = null) {

    static $loop;
    if ($newLoop) {
        $loop = $newLoop;
    } elseif (!$loop) {
        $loop = new Loop();
    }
    return $loop;

}
