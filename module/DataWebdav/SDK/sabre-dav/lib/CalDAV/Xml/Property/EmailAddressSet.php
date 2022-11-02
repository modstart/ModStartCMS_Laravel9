<?php

namespace Sabre\CalDAV\Xml\Property;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;


class EmailAddressSet implements XmlSerializable {

    
    private $emails;

    
    function __construct(array $emails) {

        $this->emails = $emails;

    }

    
    function getValue() {

        return $this->emails;

    }

    
    function xmlSerialize(Writer $writer) {

        foreach ($this->emails as $email) {

            $writer->writeElement('{http://calendarserver.org/ns/}email-address', $email);

        }

    }

}
