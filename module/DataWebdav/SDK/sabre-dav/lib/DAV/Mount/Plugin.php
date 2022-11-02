<?php

namespace Sabre\DAV\Mount;

use Sabre\DAV;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;


class Plugin extends DAV\ServerPlugin {

    
    protected $server;

    
    function initialize(DAV\Server $server) {

        $this->server = $server;
        $this->server->on('method:GET', [$this, 'httpGet'], 90);

    }

    
    function httpGet(RequestInterface $request, ResponseInterface $response) {

        $queryParams = $request->getQueryParameters();
        if (!array_key_exists('mount', $queryParams)) return;

        $currentUri = $request->getAbsoluteUrl();

                list($currentUri) = explode('?', $currentUri);

        $this->davMount($response, $currentUri);

                return false;

    }

    
    function davMount(ResponseInterface $response, $uri) {

        $response->setStatus(200);
        $response->setHeader('Content-Type', 'application/davmount+xml');
        ob_start();
        echo '<?xml version="1.0"?>', "\n";
        echo "<dm:mount xmlns:dm=\"http://purl.org/NET/webdav/mount\">\n";
        echo "  <dm:url>", htmlspecialchars($uri, ENT_NOQUOTES, 'UTF-8'), "</dm:url>\n";
        echo "</dm:mount>";
        $response->setBody(ob_get_clean());

    }


}
