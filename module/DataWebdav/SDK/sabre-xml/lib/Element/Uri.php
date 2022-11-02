<?php

namespace Sabre\Xml\Element;

use Sabre\Xml;


class Uri implements Xml\Element {

    
    protected $value;

    
    function __construct($value)
    {
        $this->value = $value;
    }

    
    function xmlSerialize(Xml\Writer $writer) {

        $writer->text(
            \Sabre\Uri\resolve(
                $writer->contextUri,
                $this->value
            )
        );

    }

    
    static function xmlDeserialize(Xml\Reader $reader) {

        return new self(
            \Sabre\Uri\resolve(
                $reader->contextUri,
                $reader->readText()
            )
        );

    }

}
