<?php

namespace Sabre\Xml\Deserializer;

use Sabre\Xml\Reader;




function keyValue(Reader $reader, $namespace = null) {

        if ($reader->isEmptyElement) {
        $reader->next();
        return [];
    }

    if (!$reader->read()) {
        $reader->next();

        return [];
    }

    if (Reader::END_ELEMENT === $reader->nodeType) {
        $reader->next();

        return [];
    }

    $values = [];

    do {

        if ($reader->nodeType === Reader::ELEMENT) {
            if ($namespace !== null && $reader->namespaceURI === $namespace) {
                $values[$reader->localName] = $reader->parseCurrentElement()['value'];
            } else {
                $clark = $reader->getClark();
                $values[$clark] = $reader->parseCurrentElement()['value'];
            }
        } else {
            if (!$reader->read()) {
                break;
            }
        }
    } while ($reader->nodeType !== Reader::END_ELEMENT);

    $reader->read();

    return $values;

}


function enum(Reader $reader, $namespace = null) {

        if ($reader->isEmptyElement) {
        $reader->next();
        return [];
    }
    if (!$reader->read()) {
        $reader->next();

        return [];
    }

    if (Reader::END_ELEMENT === $reader->nodeType) {
        $reader->next();

        return [];
    }
    $currentDepth = $reader->depth;

    $values = [];
    do {

        if ($reader->nodeType !== Reader::ELEMENT) {
            continue;
        }
        if (!is_null($namespace) && $namespace === $reader->namespaceURI) {
            $values[] = $reader->localName;
        } else {
            $values[] = $reader->getClark();
        }

    } while ($reader->depth >= $currentDepth && $reader->next());

    $reader->next();
    return $values;

}


function valueObject(Reader $reader, $className, $namespace) {

    $valueObject = new $className();
    if ($reader->isEmptyElement) {
        $reader->next();
        return $valueObject;
    }

    $defaultProperties = get_class_vars($className);

    $reader->read();
    do {

        if ($reader->nodeType === Reader::ELEMENT && $reader->namespaceURI == $namespace) {

            if (property_exists($valueObject, $reader->localName)) {
                if (is_array($defaultProperties[$reader->localName])) {
                    $valueObject->{$reader->localName}[] = $reader->parseCurrentElement()['value'];
                } else {
                    $valueObject->{$reader->localName} = $reader->parseCurrentElement()['value'];
                }
            } else {
                                $reader->next();
            }
        } else {
            if (!$reader->read()) {
                break;
            }
        }
    } while ($reader->nodeType !== Reader::END_ELEMENT);

    $reader->read();
    return $valueObject;

}


function repeatingElements(Reader $reader, $childElementName) {

    if ($childElementName[0] !== '{') {
        $childElementName = '{}' . $childElementName;
    }
    $result = [];

    foreach ($reader->parseGetElements() as $element) {

        if ($element['name'] === $childElementName) {
            $result[] = $element['value'];
        }

    }

    return $result;

}
