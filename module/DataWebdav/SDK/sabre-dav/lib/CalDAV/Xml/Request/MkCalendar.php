<?php

namespace Sabre\CalDAV\Xml\Request;

use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;


class MkCalendar implements XmlDeserializable {

    
    public $properties = [];

    
    function getProperties() {

        return $this->properties;

    }

    
    static function xmlDeserialize(Reader $reader) {

        $self = new self();

        $elementMap = $reader->elementMap;
        $elementMap['{DAV:}prop'] = 'Sabre\DAV\Xml\Element\Prop';
        $elementMap['{DAV:}set'] = 'Sabre\Xml\Element\KeyValue';
        $elems = $reader->parseInnerTree($elementMap);

        foreach ($elems as $elem) {
            if ($elem['name'] === '{DAV:}set') {
                $self->properties = array_merge($self->properties, $elem['value']['{DAV:}prop']);
            }
        }

        return $self;

    }

}
