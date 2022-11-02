<?php

namespace Sabre\Xml\Element;

use Sabre\Xml;


class Base implements Xml\Element {

    
    protected $value;

    
    function __construct($value = null) {

        $this->value = $value;

    }

    
    function xmlSerialize(Xml\Writer $writer) {

        $writer->write($this->value);

    }

    
    static function xmlDeserialize(Xml\Reader $reader) {

        $subTree = $reader->parseInnerTree();
        return $subTree;

    }

}
