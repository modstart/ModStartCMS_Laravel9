<?php

namespace Sabre\DAVACL\Xml\Request;

use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;


class ExpandPropertyReport implements XmlDeserializable {

    
    public $properties;

    
    static function xmlDeserialize(Reader $reader) {

        $elems = $reader->parseInnerTree();

        $obj = new self();
        $obj->properties = self::traverse($elems);

        return $obj;

    }

    
    private static function traverse($elems) {

        $result = [];

        foreach ($elems as $elem) {

            if ($elem['name'] !== '{DAV:}property') {
                continue;
            }

            $namespace = isset($elem['attributes']['namespace']) ?
                $elem['attributes']['namespace'] :
                'DAV:';

            $propName = '{' . $namespace . '}' . $elem['attributes']['name'];

            $value = null;
            if (is_array($elem['value'])) {
                $value = self::traverse($elem['value']);
            }

            $result[$propName] = $value;

        }

        return $result;

    }

}
