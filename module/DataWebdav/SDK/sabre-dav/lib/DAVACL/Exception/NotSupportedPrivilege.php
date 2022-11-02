<?php

namespace Sabre\DAVACL\Exception;

use Sabre\DAV;


class NotSupportedPrivilege extends DAV\Exception\PreconditionFailed {

    
    function serialize(DAV\Server $server, \DOMElement $errorNode) {

        $doc = $errorNode->ownerDocument;

        $np = $doc->createElementNS('DAV:', 'd:not-supported-privilege');
        $errorNode->appendChild($np);

    }

}
