<?php

namespace Sabre\DAV\Exception;

use Sabre\DAV;


class TooManyMatches extends Forbidden {

    
    function serialize(DAV\Server $server, \DOMElement $errorNode) {

        $error = $errorNode->ownerDocument->createElementNS('DAV:', 'd:number-of-matches-within-limits');
        $errorNode->appendChild($error);

    }

}
