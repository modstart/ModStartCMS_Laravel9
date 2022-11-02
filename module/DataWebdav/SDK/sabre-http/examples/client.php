<?php


use Sabre\HTTP\Client;
use Sabre\HTTP\Request;

$paths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../autoload.php',
    __DIR__ . '/vendor/autoload.php',

];

foreach ($paths as $path) {
    if (file_exists($path)) {
        include $path;
        break;
    }
}

$request = new Request('GET', 'http://localhost/');

$client = new Client();
$response = $client->send($request);

echo "Response:\n";

echo (string)$response;
