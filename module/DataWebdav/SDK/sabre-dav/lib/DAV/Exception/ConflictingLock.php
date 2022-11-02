<?php

namespace Sabre\DAV\Exception;

use Sabre\DAV;


class ConflictingLock extends Locked {

    
    function serialize(DAV\Server $server, \DOMElement $errorNode) {

        if ($this->lock) {
            $error = $errorNode->ownerDocument->createElementNS('DAV:', 'd:no-conflicting-lock');
            $errorNode->appendChild($error);
            $error->appendChild($errorNode->ownerDocument->createElementNS('DAV:', 'd:href', $this->lock->uri));
        }

    }

}
