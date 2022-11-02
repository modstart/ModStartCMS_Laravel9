<?php

namespace Sabre\DAV\Exception;


class InvalidResourceType extends Forbidden {

    
    function serialize(\Sabre\DAV\Server $server, \DOMElement $errorNode) {

        $error = $errorNode->ownerDocument->createElementNS('DAV:', 'd:valid-resourcetype');
        $errorNode->appendChild($error);

    }

}
