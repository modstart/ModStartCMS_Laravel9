<?php

namespace Sabre\DAV\Xml\Request;

use Sabre\Xml\Element;
use Sabre\Xml\Reader;
use Sabre\Xml\Writer;


class PropPatch implements Element {

    
    public $properties = [];

    
    function xmlSerialize(Writer $writer) {

        foreach ($this->properties as $propertyName => $propertyValue) {

            if (is_null($propertyValue)) {
                $writer->startElement("{DAV:}remove");
                $writer->write(['{DAV:}prop' => [$propertyName => $propertyValue]]);
                $writer->endElement();
            } else {
                $writer->startElement("{DAV:}set");
                $writer->write(['{DAV:}prop' => [$propertyName => $propertyValue]]);
                $writer->endElement();
            }

        }

    }

    
    static function xmlDeserialize(Reader $reader) {

        $self = new self();

        $elementMap = $reader->elementMap;
        $elementMap['{DAV:}prop'] = 'Sabre\DAV\Xml\Element\Prop';
        $elementMap['{DAV:}set'] = 'Sabre\Xml\Element\KeyValue';
        $elementMap['{DAV:}remove'] = 'Sabre\Xml\Element\KeyValue';

        $elems = $reader->parseInnerTree($elementMap);

        foreach ($elems as $elem) {
            if ($elem['name'] === '{DAV:}set') {
                $self->properties = array_merge($self->properties, $elem['value']['{DAV:}prop']);
            }
            if ($elem['name'] === '{DAV:}remove') {

                                foreach ($elem['value']['{DAV:}prop'] as $remove => $value) {
                    $self->properties[$remove] = null;
                }

            }
        }

        return $self;

    }

}
