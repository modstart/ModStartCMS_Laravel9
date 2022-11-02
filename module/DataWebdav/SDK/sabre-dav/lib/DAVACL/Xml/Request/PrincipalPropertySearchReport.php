<?php

namespace Sabre\DAVACL\Xml\Request;

use Sabre\DAV\Exception\BadRequest;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;


class PrincipalPropertySearchReport implements XmlDeserializable {

    
    public $properties;

    
    public $searchProperties = [];

    
    public $applyToPrincipalCollectionSet = false;

    
    public $test;

    
    static function xmlDeserialize(Reader $reader) {

        $self = new self();

        $foundSearchProp = false;
        $self->test = 'allof';
        if ($reader->getAttribute('test') === 'anyof') {
            $self->test = 'anyof';
        }

        $elemMap = [
            '{DAV:}property-search' => 'Sabre\\Xml\\Element\\KeyValue',
            '{DAV:}prop'            => 'Sabre\\Xml\\Element\\KeyValue',
        ];
        
        foreach ($reader->parseInnerTree($elemMap) as $elem) {

            switch ($elem['name']) {

                case '{DAV:}prop' :
                    $self->properties = array_keys($elem['value']);
                    break;
                case '{DAV:}property-search' :
                    $foundSearchProp = true;
                                                                                                    if (!isset($elem['value']['{DAV:}prop']) || !isset($elem['value']['{DAV:}match'])) {
                        throw new BadRequest('The {DAV:}property-search element must contain one {DAV:}match and one {DAV:}prop element');
                    }
                    foreach ($elem['value']['{DAV:}prop'] as $propName => $discard) {
                        $self->searchProperties[$propName] = $elem['value']['{DAV:}match'];
                    }
                    break;
                case '{DAV:}apply-to-principal-collection-set' :
                    $self->applyToPrincipalCollectionSet = true;
                    break;

            }

        }
        if (!$foundSearchProp) {
            throw new BadRequest('The {DAV:}principal-property-search report must contain at least 1 {DAV:}property-search element');
        }

        return $self;

    }

}
