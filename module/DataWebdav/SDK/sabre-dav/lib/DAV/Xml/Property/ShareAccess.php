<?php

namespace Sabre\DAV\Xml\Property;

use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Sharing\Plugin as SharingPlugin;
use Sabre\Xml\Element;
use Sabre\Xml\Reader;
use Sabre\Xml\Writer;


class ShareAccess implements Element {

    
    protected $value;

    
    function __construct($shareAccess) {

        $this->value = $shareAccess;

    }

    
    function getValue() {

        return $this->value;

    }

    
    function xmlSerialize(Writer $writer) {

        switch ($this->value) {

            case SharingPlugin::ACCESS_NOTSHARED :
                $writer->writeElement('{DAV:}not-shared');
                break;
            case SharingPlugin::ACCESS_SHAREDOWNER :
                $writer->writeElement('{DAV:}shared-owner');
                break;
            case SharingPlugin::ACCESS_READ :
                $writer->writeElement('{DAV:}read');
                break;
            case SharingPlugin::ACCESS_READWRITE :
                $writer->writeElement('{DAV:}read-write');
                break;
            case SharingPlugin::ACCESS_NOACCESS :
                $writer->writeElement('{DAV:}no-access');
                break;

        }

    }

    
    static function xmlDeserialize(Reader $reader) {

        $elems = $reader->parseInnerTree();
        foreach ($elems as $elem) {
            switch ($elem['name']) {
                case '{DAV:}not-shared' :
                    return new self(SharingPlugin::ACCESS_NOTSHARED);
                case '{DAV:}shared-owner' :
                    return new self(SharingPlugin::ACCESS_SHAREDOWNER);
                case '{DAV:}read' :
                    return new self(SharingPlugin::ACCESS_READ);
                case '{DAV:}read-write' :
                    return new self(SharingPlugin::ACCESS_READWRITE);
                case '{DAV:}no-access' :
                    return new self(SharingPlugin::ACCESS_NOACCESS);
            }
        }
        throw new BadRequest('Invalid value for {DAV:}share-access element');

    }
}
