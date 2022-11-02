<?php

namespace Sabre\Xml\Element;

use Sabre\Xml;


class Cdata implements Xml\XmlSerializable
{
    
    protected $value;

    
    function __construct($value)
    {
        $this->value = $value;
    }

    
    function xmlSerialize(Xml\Writer $writer) {

        $writer->writeCData($this->value);

    }

}
