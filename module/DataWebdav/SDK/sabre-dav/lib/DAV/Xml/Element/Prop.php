<?php

namespace Sabre\DAV\Xml\Element;

use Sabre\DAV\Xml\Property\Complex;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;


class Prop implements XmlDeserializable {

    
    static function xmlDeserialize(Reader $reader) {

                if ($reader->isEmptyElement) {
            $reader->next();
            return [];
        }

        $values = [];

        $reader->read();
        do {

            if ($reader->nodeType === Reader::ELEMENT) {

                $clark = $reader->getClark();
                $values[$clark] = self::parseCurrentElement($reader)['value'];

            } else {
                $reader->read();
            }

        } while ($reader->nodeType !== Reader::END_ELEMENT);

        $reader->read();

        return $values;

    }

    
    private static function parseCurrentElement(Reader $reader) {

        $name = $reader->getClark();

        if (array_key_exists($name, $reader->elementMap)) {
            $deserializer = $reader->elementMap[$name];
            if (is_subclass_of($deserializer, 'Sabre\\Xml\\XmlDeserializable')) {
                $value = call_user_func([$deserializer, 'xmlDeserialize'], $reader);
            } elseif (is_callable($deserializer)) {
                $value = call_user_func($deserializer, $reader);
            } else {
                $type = gettype($deserializer);
                if ($type === 'string') {
                    $type .= ' (' . $deserializer . ')';
                } elseif ($type === 'object') {
                    $type .= ' (' . get_class($deserializer) . ')';
                }
                throw new \LogicException('Could not use this type as a deserializer: ' . $type);
            }
        } else {
            $value = Complex::xmlDeserialize($reader);
        }

        return [
            'name'  => $name,
            'value' => $value,
        ];

    }

}
