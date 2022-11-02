<?php


$userList = [
    "user1" => "password",
    "user2" => "password",
];

use Sabre\HTTP\Auth;
use Sabre\HTTP\Response;
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
$response = new Response();

$basicAuth = new Auth\Basic("Locked down area", $request, $response);
if (!$userPass = $basicAuth->getCredentials()) {

        $basicAuth->requireLogin();

} elseif (!isset($userList[$userPass[0]]) || $userList[$userPass[0]] !== $userPass[1]) {

        $basicAuth->requireLogin();
} else {

        $response->setBody('You are logged in!');

}

Sapi::sendResponse($response);
