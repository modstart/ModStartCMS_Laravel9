<?php

namespace Sabre\DAVACL\Exception;

use Sabre\DAV;


class AceConflict extends DAV\Exception\Conflict {

    
    function serialize(DAV\Server $server, \DOMElement $errorNode) {

        $doc = $errorNode->ownerDocument;

        $np = $doc->createElementNS('DAV:', 'd:no-ace-conflict');
        $errorNode->appendChild($np);

    }

}
