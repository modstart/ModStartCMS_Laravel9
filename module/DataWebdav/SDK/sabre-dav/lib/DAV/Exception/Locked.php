<?php

namespace Sabre\DAV\Exception;

use Sabre\DAV;


class Locked extends DAV\Exception {

    
    protected $lock;

    
    function __construct(DAV\Locks\LockInfo $lock = null) {

        $this->lock = $lock;

    }

    
    function getHTTPCode() {

        return 423;

    }

    
    function serialize(DAV\Server $server, \DOMElement $errorNode) {

        if ($this->lock) {
            $error = $errorNode->ownerDocument->createElementNS('DAV:', 'd:lock-token-submitted');
            $errorNode->appendChild($error);

            $href = $errorNode->ownerDocument->createElementNS('DAV:', 'd:href');
            $href->appendChild($errorNode->ownerDocument->createTextNode($this->lock->uri));
            $error->appendChild(
                $href
            );
        }

    }

}
