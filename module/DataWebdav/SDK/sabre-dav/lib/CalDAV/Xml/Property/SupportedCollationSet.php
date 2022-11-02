<?php

namespace Sabre\CalDAV\Xml\Property;

use Sabre\CalDAV\Plugin;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;


class SupportedCollationSet implements XmlSerializable {

    
    function xmlSerialize(Writer $writer) {

        $collations = [
            'i;ascii-casemap',
            'i;octet',
            'i;unicode-casemap'
        ];

        foreach ($collations as $collation) {
            $writer->writeElement('{' . Plugin::NS_CALDAV . '}supported-collation', $collation);
        }

    }

}
