<?php

namespace Sabre\DAV\Exception;

use Sabre\DAV;


class ReportNotSupported extends UnsupportedMediaType {

    
    function serialize(DAV\Server $server, \DOMElement $errorNode) {

        $error = $errorNode->ownerDocument->createElementNS('DAV:', 'd:supported-report');
        $errorNode->appendChild($error);

    }

}
