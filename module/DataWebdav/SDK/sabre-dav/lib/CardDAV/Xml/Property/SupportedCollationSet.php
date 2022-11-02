<?php

namespace Sabre\CardDAV\Xml\Property;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;


class SupportedCollationSet implements XmlSerializable {

    
    function xmlSerialize(Writer $writer) {

        foreach (['i;ascii-casemap', 'i;octet', 'i;unicode-casemap'] as $coll) {
            $writer->writeElement('{urn:ietf:params:xml:ns:carddav}supported-collation', $coll);
        }

    }

}
