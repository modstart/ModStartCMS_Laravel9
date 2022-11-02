<?php

namespace Sabre\Xml\Serializer;

use InvalidArgumentException;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;




function enum(Writer $writer, array $values) {

    foreach ($values as $value) {
        $writer->writeElement($value);
    }
}


function valueObject(Writer $writer, $valueObject, $namespace) {
    foreach (get_object_vars($valueObject) as $key => $val) {
        if (is_array($val)) {
                                    foreach ($val as $child) {
                $writer->writeElement('{' . $namespace . '}' . $key, $child);
            }

        } elseif ($val !== null) {
            $writer->writeElement('{' . $namespace . '}' . $key, $val);
        }
    }
}



function repeatingElements(Writer $writer, array $items, $childElementName) {

    foreach ($items as $item) {
        $writer->writeElement($childElementName, $item);
    }

}


function standardSerializer(Writer $writer, $value) {

    if (is_scalar($value)) {

                $writer->text($value);

    } elseif ($value instanceof XmlSerializable) {

                $value->xmlSerialize($writer);

    } elseif (is_object($value) && isset($writer->classMap[get_class($value)])) {

                $writer->classMap[get_class($value)]($writer, $value);

    } elseif (is_callable($value)) {

                $value($writer);

    } elseif (is_null($value)) {

        
    } elseif (is_array($value) && array_key_exists('name', $value)) {

                
        $name = $value['name'];
        $attributes = isset($value['attributes']) ? $value['attributes'] : [];
        $value = isset($value['value']) ? $value['value'] : null;

        $writer->startElement($name);
        $writer->writeAttributes($attributes);
        $writer->write($value);
        $writer->endElement();

    } elseif (is_array($value)) {

        foreach ($value as $name => $item) {

            if (is_int($name)) {

                                                standardSerializer($writer, $item);

            } elseif (is_string($name) && is_array($item) && isset($item['attributes'])) {

                                                $writer->startElement($name);
                $writer->writeAttributes($item['attributes']);
                if (isset($item['value'])) {
                    $writer->write($item['value']);
                }
                $writer->endElement();

            } elseif (is_string($name)) {

                                $writer->startElement($name);
                $writer->write($item);
                $writer->endElement();

            } else {

                throw new InvalidArgumentException('The writer does not know how to serialize arrays with keys of type: ' . gettype($name));

            }
        }

    } elseif (is_object($value)) {

        throw new InvalidArgumentException('The writer cannot serialize objects of class: ' . get_class($value));

    } else {

        throw new InvalidArgumentException('The writer cannot serialize values of type: ' . gettype($value));

    }

}
