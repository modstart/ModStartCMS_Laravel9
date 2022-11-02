<?php

namespace Sabre\CalDAV\Exception;

use Sabre\CalDAV;
use Sabre\DAV;


class InvalidComponentType extends DAV\Exception\Forbidden {

    
    function serialize(DAV\Server $server, \DOMElement $errorNode) {

        $doc = $errorNode->ownerDocument;

        $np = $doc->createElementNS(CalDAV\Plugin::NS_CALDAV, 'cal:supported-calendar-component');
        $errorNode->appendChild($np);

    }

}
