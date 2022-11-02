<?php


include 'vendor/autoload.php';

$server = new Sabre\DAV\Server([
    new Sabre\DAV\FS\Directory(__DIR__)
]);


$server->addPlugin(
    new Sabre\DAV\Browser\Plugin()
);

$server->exec();
