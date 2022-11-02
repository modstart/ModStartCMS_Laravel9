<?php

namespace Sabre\DAV\Exception;

use Sabre\DAV;


class InvalidSyncToken extends Forbidden {

    
    function serialize(DAV\Server $server, \DOMElement $errorNode) {

        $error = $errorNode->ownerDocument->createElementNS('DAV:', 'd:valid-sync-token');
        $errorNode->appendChild($error);

    }

}
