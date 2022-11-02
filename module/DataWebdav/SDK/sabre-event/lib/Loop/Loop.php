<?php

namespace Sabre\Event\Loop;


class Loop {

    
    function setTimeout(callable $cb, $timeout) {

        $triggerTime = microtime(true) + ($timeout);

        if (!$this->timers) {
                        $this->timers[] = [$triggerTime, $cb];
            return;
        }

                                        $index = count($this->timers) - 1;
        while (true) {
            if ($triggerTime < $this->timers[$index][0]) {
                array_splice(
                    $this->timers,
                    $index + 1,
                    0,
                    [[$triggerTime, $cb]]
                );
                break;
            } elseif ($index === 0) {
                array_unshift($this->timers, [$triggerTime, $cb]);
                break;
            }
            $index--;

        }

    }

    
    function setInterval(callable $cb, $timeout) {

        $keepGoing = true;
        $f = null;

        $f = function() use ($cb, &$f, $timeout, &$keepGoing) {
            if ($keepGoing) {
                $cb();
                $this->setTimeout($f, $timeout);
            }
        };
        $this->setTimeout($f, $timeout);

                                                        return ['I\'m an implementation detail', &$keepGoing];

    }

    
    function clearInterval($intervalId) {

        $intervalId[1] = false;

    }

    
    function nextTick(callable $cb) {

        $this->nextTick[] = $cb;

    }


    
    function addReadStream($stream, callable $cb) {

        $this->readStreams[(int)$stream] = $stream;
        $this->readCallbacks[(int)$stream] = $cb;

    }

    
    function addWriteStream($stream, callable $cb) {

        $this->writeStreams[(int)$stream] = $stream;
        $this->writeCallbacks[(int)$stream] = $cb;

    }

    
    function removeReadStream($stream) {

        unset(
            $this->readStreams[(int)$stream],
            $this->readCallbacks[(int)$stream]
        );

    }

    
    function removeWriteStream($stream) {

        unset(
            $this->writeStreams[(int)$stream],
            $this->writeCallbacks[(int)$stream]
        );

    }


    
    function run() {

        $this->running = true;

        do {

            $hasEvents = $this->tick(true);

        } while ($this->running && $hasEvents);
        $this->running = false;

    }

    
    function tick($block = false) {

        $this->runNextTicks();
        $nextTimeout = $this->runTimers();

                if (!$block) {
                        $streamWait = 0;
        } elseif ($this->nextTick) {
                        $streamWait = 0;
        } elseif (is_numeric($nextTimeout)) {
                        $streamWait = $nextTimeout;
        } else {
                        $streamWait = null;
        }

        $this->runStreams($streamWait);

        return ($this->readStreams || $this->writeStreams || $this->nextTick || $this->timers);

    }

    
    function stop() {

        $this->running = false;

    }

    
    protected function runNextTicks() {

        $nextTick = $this->nextTick;
        $this->nextTick = [];

        foreach ($nextTick as $cb) {
            $cb();
        }

    }

    
    protected function runTimers() {

        $now = microtime(true);
        while (($timer = array_pop($this->timers)) && $timer[0] < $now) {
            $timer[1]();
        }
                if ($timer) {
            $this->timers[] = $timer;
            return $timer[0] - microtime(true);
        }

    }

    
    protected function runStreams($timeout) {

        if ($this->readStreams || $this->writeStreams) {

            $read = $this->readStreams;
            $write = $this->writeStreams;
            $except = null;
            if (stream_select($read, $write, $except, null, $timeout)) {

                                                foreach ($read as $readStream) {
                    $readCb = $this->readCallbacks[(int)$readStream];
                    $readCb();
                }
                foreach ($write as $writeStream) {
                    $writeCb = $this->writeCallbacks[(int)$writeStream];
                    $writeCb();
                }

            }

        } elseif ($this->running && ($this->nextTick || $this->timers)) {
            usleep($timeout !== null ? $timeout * 1000000 : 200000);
        }

    }

    
    protected $running = false;

    
    protected $timers = [];

    
    protected $nextTick = [];

    
    protected $readStreams = [];

    
    protected $writeStreams = [];

    
    protected $readCallbacks = [];

    
    protected $writeCallbacks = [];


}
