<?php

namespace Sabre\DAVACL\Exception;

use Sabre\DAV;


class NoAbstract extends DAV\Exception\PreconditionFailed {

    
    function serialize(DAV\Server $server, \DOMElement $errorNode) {

        $doc = $errorNode->ownerDocument;

        $np = $doc->createElementNS('DAV:', 'd:no-abstract');
        $errorNode->appendChild($np);

    }

}
