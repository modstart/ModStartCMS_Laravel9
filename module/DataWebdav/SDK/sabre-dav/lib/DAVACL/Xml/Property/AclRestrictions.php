<?php

namespace Sabre\DAVACL\Xml\Property;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;


class AclRestrictions implements XmlSerializable {

    
    function xmlSerialize(Writer $writer) {

        $writer->writeElement('{DAV:}grant-only');
        $writer->writeElement('{DAV:}no-invert');

    }

}
