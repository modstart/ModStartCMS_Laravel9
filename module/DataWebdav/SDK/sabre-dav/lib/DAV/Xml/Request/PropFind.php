<?php

namespace Sabre\DAV\Xml\Request;

use Sabre\Xml\Element\KeyValue;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;


class PropFind implements XmlDeserializable {

    
    public $allProp = false;

    
    public $properties;

    
    static function xmlDeserialize(Reader $reader) {

        $self = new self();

        $reader->pushContext();
        $reader->elementMap['{DAV:}prop'] = 'Sabre\Xml\Element\Elements';

        foreach (KeyValue::xmlDeserialize($reader) as $k => $v) {

            switch ($k) {
                case '{DAV:}prop' :
                    $self->properties = $v;
                    break;
                case '{DAV:}allprop' :
                    $self->allProp = true;

            }

        }

        $reader->popContext();

        return $self;

    }

}
