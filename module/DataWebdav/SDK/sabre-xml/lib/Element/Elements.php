<?php

namespace Sabre\Xml\Element;

use Sabre\Xml;
use Sabre\Xml\Deserializer;
use Sabre\Xml\Serializer;


class Elements implements Xml\Element {

    
    protected $value;

    
    function __construct(array $value = []) {

        $this->value = $value;

    }

    
    function xmlSerialize(Xml\Writer $writer) {

        Serializer\enum($writer, $this->value);

    }

    
    static function xmlDeserialize(Xml\Reader $reader) {

        return Deserializer\enum($reader);

    }

}
