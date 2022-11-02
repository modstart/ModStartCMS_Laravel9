<?php

namespace Sabre\DAV\Xml\Property;

use Sabre\DAV\Xml\Element\Sharee;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;


class Invite implements XmlSerializable {

    
    public $sharees = [];

    
    function __construct(array $sharees) {

        $this->sharees = $sharees;

    }

    
    function xmlSerialize(Writer $writer) {

        foreach ($this->sharees as $sharee) {
            $writer->writeElement('{DAV:}sharee', $sharee);
        }

    }

}
