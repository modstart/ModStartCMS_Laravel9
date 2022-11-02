<?php

namespace Sabre\DAV\Xml\Property;

use Sabre\DAV\Browser\HtmlOutput;
use Sabre\DAV\Browser\HtmlOutputHelper;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;


class SupportedMethodSet implements XmlSerializable, HtmlOutput {

    
    protected $methods = [];

    
    function __construct(array $methods) {

        $this->methods = $methods;

    }

    
    function getValue() {

        return $this->methods;

    }

    
    function has($methodName) {

        return in_array(
            $methodName,
            $this->methods
        );

    }

    
    function xmlSerialize(Writer $writer) {

        foreach ($this->getValue() as $val) {
            $writer->startElement('{DAV:}supported-method');
            $writer->writeAttribute('name', $val);
            $writer->endElement();
        }

    }

    
    function toHtml(HtmlOutputHelper $html) {

        return implode(
            ', ',
            array_map([$html, 'h'], $this->getValue())
        );

    }

}
