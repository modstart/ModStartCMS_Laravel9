<?php



date_default_timezone_set('Canada/Eastern');



$pdo = new PDO('sqlite:data/db.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function exception_error_handler($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler("exception_error_handler");

require_once 'vendor/autoload.php';

$authBackend = new Sabre\DAV\Auth\Backend\PDO($pdo);
$calendarBackend = new Sabre\CalDAV\Backend\PDO($pdo);
$principalBackend = new Sabre\DAVACL\PrincipalBackend\PDO($pdo);

$tree = [
    new Sabre\CalDAV\Principal\Collection($principalBackend),
    new Sabre\CalDAV\CalendarRoot($principalBackend, $calendarBackend),
];

$server = new Sabre\DAV\Server($tree);

if (isset($baseUri))
    $server->setBaseUri($baseUri);


$authPlugin = new Sabre\DAV\Auth\Plugin($authBackend);
$server->addPlugin($authPlugin);

$aclPlugin = new Sabre\DAVACL\Plugin();
$server->addPlugin($aclPlugin);


$caldavPlugin = new Sabre\CalDAV\Plugin();
$server->addPlugin($caldavPlugin);


$server->addPlugin(
    new Sabre\CalDAV\Subscriptions\Plugin()
);


$server->addPlugin(
    new Sabre\CalDAV\Schedule\Plugin()
);


$server->addPlugin(new Sabre\DAV\Sync\Plugin());


$server->addPlugin(new Sabre\DAV\Sharing\Plugin());
$server->addPlugin(new Sabre\CalDAV\SharingPlugin());

$browser = new Sabre\DAV\Browser\Plugin();
$server->addPlugin($browser);

$server->exec();
