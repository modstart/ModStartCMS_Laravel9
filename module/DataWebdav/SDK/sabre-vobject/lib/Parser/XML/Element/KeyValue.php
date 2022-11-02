<?php

namespace Sabre\VObject\Parser\XML\Element;

use Sabre\Xml as SabreXml;


class KeyValue extends SabreXml\Element\KeyValue
{
    
    public static function xmlDeserialize(SabreXml\Reader $reader)
    {
                if ($reader->isEmptyElement) {
            $reader->next();

            return [];
        }

        $values = [];
        $reader->read();

        do {
            if (SabreXml\Reader::ELEMENT === $reader->nodeType) {
                $name = $reader->localName;
                $values[$name] = $reader->parseCurrentElement()['value'];
            } else {
                $reader->read();
            }
        } while (SabreXml\Reader::END_ELEMENT !== $reader->nodeType);

        $reader->read();

        return $values;
    }
}
