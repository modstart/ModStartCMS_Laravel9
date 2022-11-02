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

for ($i = 0; $i < 1000; $i++) {

    echo "$i sending\n";
    $client->sendAsync(
        $request,

                function($response) use ($i) {
            echo "$i -> " . $response->getStatus() . "\n";
        },

                                        function($error) use ($i) {

            if ($error['status'] === Client::STATUS_CURLERROR) {
                                echo "$i -> curl error: " . $error['curl_errmsg'] . "\n";
            } else {
                                echo "$i -> " . $error['response']->getStatus() . "\n";
            }
        }
    );
}

$client->wait();
