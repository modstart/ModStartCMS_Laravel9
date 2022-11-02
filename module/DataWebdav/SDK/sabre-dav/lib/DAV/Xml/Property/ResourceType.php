<?php

namespace Sabre\DAV\Xml\Property;

use Sabre\DAV\Browser\HtmlOutput;
use Sabre\DAV\Browser\HtmlOutputHelper;
use Sabre\Xml\Element;
use Sabre\Xml\Reader;


class ResourceType extends Element\Elements implements HtmlOutput {

    
    function __construct($resourceTypes = null) {

        parent::__construct((array)$resourceTypes);

    }

    
    function getValue() {

        return $this->value;

    }

    
    function is($type) {

        return in_array($type, $this->value);

    }

    
    function add($type) {

        $this->value[] = $type;
        $this->value = array_unique($this->value);

    }

    
    static function xmlDeserialize(Reader $reader) {

        return
            new self(parent::xmlDeserialize($reader));

    }

    
    function toHtml(HtmlOutputHelper $html) {

        return implode(
            ', ',
            array_map([$html, 'xmlName'], $this->getValue())
        );

    }

}
