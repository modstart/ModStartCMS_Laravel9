#!/usr/bin/env php
<?php


if ($argc < 2) {
    echo "Usage: " . $argv[0] . " filename\n";
    exit(1);
}

require __DIR__ . '/../vendor/autoload.php';

$tail = popen('tail -fn0 ' . escapeshellarg($argv[1]), 'r');

\Sabre\Event\Loop\addReadStream($tail, function() use ($tail) {

    echo fread($tail, 4096);

});

$loop->run();
