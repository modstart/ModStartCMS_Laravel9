<?php



date_default_timezone_set('Canada/Eastern');
$publicDir = 'public';
$tmpDir = 'tmpdata';



require_once 'vendor/autoload.php';

$root = new \Sabre\DAV\FS\Directory($publicDir);

$server = new \Sabre\DAV\Server($root);

if (isset($baseUri))
    $server->setBaseUri($baseUri);

$lockBackend = new \Sabre\DAV\Locks\Backend\File($tmpDir . '/locksdb');
$lockPlugin = new \Sabre\DAV\Locks\Plugin($lockBackend);
$server->addPlugin($lockPlugin);

$browser = new \Sabre\DAV\Browser\Plugin();
$server->addPlugin($browser);

$server->addPlugin(new \Sabre\DAV\Browser\GuessContentType());

$authBackend = new \Sabre\DAV\Auth\Backend\File('.htdigest');
$auth = new \Sabre\DAV\Auth\Plugin($authBackend);
$server->addPlugin($auth);

$tempFF = new \Sabre\DAV\TemporaryFileFilterPlugin($tmpDir);
$server->addPlugin($tempFF);

$server->exec();
