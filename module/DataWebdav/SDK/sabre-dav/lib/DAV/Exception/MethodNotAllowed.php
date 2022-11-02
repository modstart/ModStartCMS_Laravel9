<?php

namespace Sabre\DAV\Exception;

use Sabre\DAV;


class MethodNotAllowed extends DAV\Exception {

    
    function getHTTPCode() {

        return 405;

    }

    
    function getHTTPHeaders(\Sabre\DAV\Server $server) {

        $methods = $server->getAllowedMethods($server->getRequestUri());

        return [
            'Allow' => strtoupper(implode(', ', $methods)),
        ];

    }

}
