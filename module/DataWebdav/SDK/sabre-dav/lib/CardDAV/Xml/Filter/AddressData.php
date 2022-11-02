<?php

namespace Sabre\CardDAV\Xml\Filter;

use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;


class AddressData implements XmlDeserializable {

    
    static function xmlDeserialize(Reader $reader) {

        $result = [
            'contentType' => $reader->getAttribute('content-type') ?: 'text/vcard',
            'version'     => $reader->getAttribute('version') ?: '3.0',
        ];

        $elems = (array)$reader->parseInnerTree();
        $elems = array_filter($elems, function($element) {
            return $element['name'] === '{urn:ietf:params:xml:ns:carddav}prop' &&
                isset($element['attributes']['name']);
        });
        $result['addressDataProperties'] = array_map(function($element) {
            return $element['attributes']['name'];
        }, $elems);

        return $result;
    }

}
