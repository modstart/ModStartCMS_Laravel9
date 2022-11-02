<?php

namespace Sabre\DAVACL\Xml\Request;

use Sabre\DAV\Exception\BadRequest;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;


class PrincipalSearchPropertySetReport implements XmlDeserializable {

    
    static function xmlDeserialize(Reader $reader) {

        if (!$reader->isEmptyElement) {
            throw new BadRequest('The {DAV:}principal-search-property-set element must be empty');
        }

                $reader->next();

        $self = new self();
        return $self;

    }

}
