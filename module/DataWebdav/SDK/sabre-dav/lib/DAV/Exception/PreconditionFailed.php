<?php

namespace Sabre\DAV\Exception;

use Sabre\DAV;


class PreconditionFailed extends DAV\Exception {

    
    public $header = null;

    
    function __construct($message, $header = null) {

        parent::__construct($message);
        $this->header = $header;

    }

    
    function getHTTPCode() {

        return 412;

    }

    
    function serialize(DAV\Server $server, \DOMElement $errorNode) {

        if ($this->header) {
            $prop = $errorNode->ownerDocument->createElement('s:header');
            $prop->nodeValue = $this->header;
            $errorNode->appendChild($prop);
        }

    }

}
