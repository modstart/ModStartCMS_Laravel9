<?php

namespace Sabre\CalDAV\Xml\Property;

use Sabre\CalDAV\Plugin;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;


class SupportedCalendarData implements XmlSerializable {

    
    function xmlSerialize(Writer $writer) {

        $writer->startElement('{' . Plugin::NS_CALDAV . '}calendar-data');
        $writer->writeAttributes([
            'content-type' => 'text/calendar',
            'version'      => '2.0',
        ]);
        $writer->endElement();         $writer->startElement('{' . Plugin::NS_CALDAV . '}calendar-data');
        $writer->writeAttributes([
            'content-type' => 'application/calendar+json',
        ]);
        $writer->endElement(); 
    }

}
