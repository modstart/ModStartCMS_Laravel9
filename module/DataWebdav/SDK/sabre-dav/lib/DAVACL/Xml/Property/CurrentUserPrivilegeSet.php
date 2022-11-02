<?php

namespace Sabre\DAVACL\Xml\Property;

use Sabre\DAV\Browser\HtmlOutput;
use Sabre\DAV\Browser\HtmlOutputHelper;
use Sabre\Xml\Element;
use Sabre\Xml\Reader;
use Sabre\Xml\Writer;


class CurrentUserPrivilegeSet implements Element, HtmlOutput {

    
    private $privileges;

    
    function __construct(array $privileges) {

        $this->privileges = $privileges;

    }

    
    function xmlSerialize(Writer $writer) {

        foreach ($this->privileges as $privName) {

            $writer->startElement('{DAV:}privilege');
            $writer->writeElement($privName);
            $writer->endElement();

        }


    }

    
    function has($privilegeName) {

        return in_array($privilegeName, $this->privileges);

    }

    
    function getValue() {

        return $this->privileges;

    }

    
    static function xmlDeserialize(Reader $reader) {

        $result = [];

        $tree = $reader->parseInnerTree(['{DAV:}privilege' => 'Sabre\\Xml\\Element\\Elements']);
        foreach ($tree as $element) {
            if ($element['name'] !== '{DAV:}privilege') {
                continue;
            }
            $result[] = $element['value'][0];
        }
        return new self($result);

    }

    
    function toHtml(HtmlOutputHelper $html) {

        return implode(
            ', ',
            array_map([$html, 'xmlName'], $this->getValue())
        );

    }


}
