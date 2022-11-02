<?php

namespace Sabre\DAV\Exception;

use Sabre\DAV;


class LockTokenMatchesRequestUri extends Conflict {

    
    function __construct() {

        $this->message = 'The locktoken supplied does not match any locks on this entity';

    }

    
    function serialize(DAV\Server $server, \DOMElement $errorNode) {

        $error = $errorNode->ownerDocument->createElementNS('DAV:', 'd:lock-token-matches-request-uri');
        $errorNode->appendChild($error);

    }

}
