<?php

namespace Sabre\DAV\Browser;

use Sabre\DAV;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;


class MapGetToPropFind extends DAV\ServerPlugin {

    
    protected $server;

    
    function initialize(DAV\Server $server) {

        $this->server = $server;
        $this->server->on('method:GET', [$this, 'httpGet'], 90);
    }

    
    function httpGet(RequestInterface $request, ResponseInterface $response) {

        $node = $this->server->tree->getNodeForPath($request->getPath());
        if ($node instanceof DAV\IFile) return;

        $subRequest = clone $request;
        $subRequest->setMethod('PROPFIND');

        $this->server->invokeMethod($subRequest, $response);
        return false;

    }

}
