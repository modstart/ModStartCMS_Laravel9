<?php


class CliLog {

    protected $stream;

    function __construct() {

        $this->stream = fopen('php://stdout', 'w');

    }

    function log($msg) {
        fwrite($this->stream, $msg . "\n");
    }

}

$log = new CliLog();

if (php_sapi_name() !== 'cli-server') {
    die("This script is intended to run on the built-in php webserver");
}



$paths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../autoload.php',
];

foreach ($paths as $path) {
    if (file_exists($path)) {
        include $path;
        break;
    }
}

use Sabre\DAV;

$root = new DAV\FS\Directory(getcwd());

$server = new DAV\Server($root);

$server->addPlugin(new DAV\Browser\Plugin());

$server->exec();
