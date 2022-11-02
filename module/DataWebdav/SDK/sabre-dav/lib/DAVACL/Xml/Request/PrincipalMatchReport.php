<?php

namespace Sabre\DAVACL\Xml\Request;

use Sabre\Xml\Deserializer;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;


class PrincipalMatchReport implements XmlDeserializable {

    
    const SELF = 1;

    
    const PRINCIPAL_PROPERTY = 2;

    
    public $type;

    
    public $properties = [];

    
    public $principalProperty;

    
    static function xmlDeserialize(Reader $reader) {

        $reader->pushContext();
        $reader->elementMap['{DAV:}prop'] = 'Sabre\Xml\Deserializer\enum';

        $elems = Deserializer\keyValue(
            $reader,
            'DAV:'
        );

        $reader->popContext();

        $principalMatch = new self();

        if (array_key_exists('self', $elems)) {
            $principalMatch->type = self::SELF;
        }

        if (array_key_exists('principal-property', $elems)) {
            $principalMatch->type = self::PRINCIPAL_PROPERTY;
            $principalMatch->principalProperty = $elems['principal-property'][0]['name'];
        }

        if (!empty($elems['prop'])) {
            $principalMatch->properties = $elems['prop'];
        }

        return $principalMatch;

    }

}
