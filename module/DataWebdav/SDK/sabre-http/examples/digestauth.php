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

$digestAuth = new Auth\Digest("Locked down area", $request, $response);
$digestAuth->init();
if (!$userName = $digestAuth->getUsername()) {

        $digestAuth->requireLogin();

} elseif (!isset($userList[$userName]) || !$digestAuth->validatePassword($userList[$userName])) {

        $digestAuth->requireLogin();
} else {

        $response->setBody('You are logged in!');

}

Sapi::sendResponse($response);
