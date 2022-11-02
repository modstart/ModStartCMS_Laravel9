<?php

namespace Sabre\DAV\Xml\Request;

use Sabre\DAV\Xml\Element\Sharee;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;


class ShareResource implements XmlDeserializable {

    
    public $sharees = [];

    
    function __construct(array $sharees) {

        $this->sharees = $sharees;

    }

    
    static function xmlDeserialize(Reader $reader) {

        $elems = $reader->parseInnerTree([
            '{DAV:}sharee'       => 'Sabre\DAV\Xml\Element\Sharee',
            '{DAV:}share-access' => 'Sabre\DAV\Xml\Property\ShareAccess',
            '{DAV:}prop'         => 'Sabre\Xml\Deserializer\keyValue',
        ]);

        $sharees = [];

        foreach ($elems as $elem) {
            if ($elem['name'] !== '{DAV:}sharee') continue;
            $sharees[] = $elem['value'];

        }

        return new self($sharees);

    }

}
