<?php

namespace Sabre\CalDAV\Xml\Property;

use Sabre\CalDAV\Plugin;
use Sabre\Xml\Element;
use Sabre\Xml\ParseException;
use Sabre\Xml\Reader;
use Sabre\Xml\Writer;


class SupportedCalendarComponentSet implements Element {

    
    protected $components = [];

    
    function __construct(array $components) {

        $this->components = $components;

    }

    
    function getValue() {

        return $this->components;

    }

    
    function xmlSerialize(Writer $writer) {

        foreach ($this->components as $component) {

            $writer->startElement('{' . Plugin::NS_CALDAV . '}comp');
            $writer->writeAttributes(['name' => $component]);
            $writer->endElement();

        }

    }

    
    static function xmlDeserialize(Reader $reader) {

        $elems = $reader->parseInnerTree();

        $components = [];

        foreach ((array)$elems as $elem) {
            if ($elem['name'] === '{' . Plugin::NS_CALDAV . '}comp') {
                $components[] = $elem['attributes']['name'];
            }
        }

        if (!$components) {
            throw new ParseException('supported-calendar-component-set must have at least one CALDAV:comp element');
        }

        return new self($components);

    }

}
