<?php

namespace Sabre\CalDAV\Xml\Property;

use Sabre\CalDAV\Plugin;
use Sabre\Xml\Deserializer;
use Sabre\Xml\Element;
use Sabre\Xml\Reader;
use Sabre\Xml\Writer;


class ScheduleCalendarTransp implements Element {

    const TRANSPARENT = 'transparent';
    const OPAQUE = 'opaque';

    
    protected $value;

    
    function __construct($value) {

        if ($value !== self::TRANSPARENT && $value !== self::OPAQUE) {
            throw new \InvalidArgumentException('The value must either be specified as "transparent" or "opaque"');
        }
        $this->value = $value;

    }

    
    function getValue() {

        return $this->value;

    }

    
    function xmlSerialize(Writer $writer) {

        switch ($this->value) {
            case self::TRANSPARENT :
                $writer->writeElement('{' . Plugin::NS_CALDAV . '}transparent');
                break;
            case self::OPAQUE :
                $writer->writeElement('{' . Plugin::NS_CALDAV . '}opaque');
                break;
        }

    }

    
    static function xmlDeserialize(Reader $reader) {

        $elems = Deserializer\enum($reader, Plugin::NS_CALDAV);

        if (in_array('transparent', $elems)) {
            $value = self::TRANSPARENT;
        } else {
            $value = self::OPAQUE;
        }
        return new self($value);

    }

}
