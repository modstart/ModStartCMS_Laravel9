<?php

namespace Sabre\Xml\Element;

use Sabre\Xml;
use Sabre\Xml\Deserializer;


class KeyValue implements Xml\Element {

    
    protected $value;

    
    function __construct(array $value = []) {

        $this->value = $value;

    }

    
    function xmlSerialize(Xml\Writer $writer) {

        $writer->write($this->value);

    }

    
    static function xmlDeserialize(Xml\Reader $reader) {

        return Deserializer\keyValue($reader);

    }

}
