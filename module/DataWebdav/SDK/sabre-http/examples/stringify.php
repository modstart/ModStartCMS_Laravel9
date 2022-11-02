<?php


use Sabre\HTTP\Request;
use Sabre\HTTP\Response;

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

$request = new Request('POST', '/foo');
$request->setHeaders([
    'Host'         => 'example.org',
    'Content-Type' => 'application/json'
    ]);

$request->setBody(json_encode(['foo' => 'bar']));

echo $request;
echo "\r\n\r\n";

$response = new Response(424);
$response->setHeaders([
    'Content-Type' => 'text/plain',
    'Connection'   => 'close',
    ]);

$response->setBody("ABORT! ABORT!");

echo $response;

echo "\r\n";
