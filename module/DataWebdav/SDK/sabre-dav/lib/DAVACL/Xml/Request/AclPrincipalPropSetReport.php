<?php

namespace Sabre\DAVACL\Xml\Request;

use Sabre\Xml\Deserializer;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;


class AclPrincipalPropSetReport implements XmlDeserializable {

    public $properties = [];

    
    static function xmlDeserialize(Reader $reader) {
       
        $reader->pushContext();
        $reader->elementMap['{DAV:}prop'] = 'Sabre\Xml\Deserializer\enum';

        $elems = Deserializer\keyValue(
            $reader,
            'DAV:'
        );

        $reader->popContext();

        $report = new self();

        if (!empty($elems['prop'])) {
            $report->properties = $elems['prop'];
        }

        return $report;

    }

}
