<?php

namespace Sabre\CalDAV\Xml\Property;

use Sabre\CalDAV\Plugin;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;


class AllowedSharingModes implements XmlSerializable {

    
    protected $canBeShared;

    
    protected $canBePublished;

    
    function __construct($canBeShared, $canBePublished) {

        $this->canBeShared = $canBeShared;
        $this->canBePublished = $canBePublished;

    }

    
    function xmlSerialize(Writer $writer) {

        if ($this->canBeShared) {
            $writer->writeElement('{' . Plugin::NS_CALENDARSERVER . '}can-be-shared');
        }
        if ($this->canBePublished) {
            $writer->writeElement('{' . Plugin::NS_CALENDARSERVER . '}can-be-published');
        }

    }



}
