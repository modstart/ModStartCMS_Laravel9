<?php

$remoteUrl = 'http://example.org/';

$myBaseUrl = '/reverseproxy.php';

use Sabre\HTTP\Client;
use Sabre\HTTP\Sapi;

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


$request = Sapi::getRequest();
$request->setBaseUrl($myBaseUrl);

$subRequest = clone $request;

$subRequest->removeHeader('Host');

$subRequest->setUrl($remoteUrl . $request->getPath());

$client = new Client();

$response = $client->send($subRequest);

Sapi::sendResponse($response);
