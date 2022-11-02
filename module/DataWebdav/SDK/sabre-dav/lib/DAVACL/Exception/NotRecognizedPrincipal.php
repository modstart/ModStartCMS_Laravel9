<?php

namespace Sabre\DAVACL\Exception;

use Sabre\DAV;


class NotRecognizedPrincipal extends DAV\Exception\PreconditionFailed {

    
    function serialize(DAV\Server $server, \DOMElement $errorNode) {

        $doc = $errorNode->ownerDocument;

        $np = $doc->createElementNS('DAV:', 'd:recognized-principal');
        $errorNode->appendChild($np);

    }

}
