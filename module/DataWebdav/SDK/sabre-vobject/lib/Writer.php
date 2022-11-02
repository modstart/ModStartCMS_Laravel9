<?php

namespace Sabre\VObject;

use Sabre\Xml;


class Writer
{
    
    public static function write(Component $component)
    {
        return $component->serialize();
    }

    
    public static function writeJson(Component $component, $options = 0)
    {
        return json_encode($component, $options);
    }

    
    public static function writeXml(Component $component)
    {
        $writer = new Xml\Writer();
        $writer->openMemory();
        $writer->setIndent(true);

        $writer->startDocument('1.0', 'utf-8');

        if ($component instanceof Component\VCalendar) {
            $writer->startElement('icalendar');
            $writer->writeAttribute('xmlns', Parser\XML::XCAL_NAMESPACE);
        } else {
            $writer->startElement('vcards');
            $writer->writeAttribute('xmlns', Parser\XML::XCARD_NAMESPACE);
        }

        $component->xmlSerialize($writer);

        $writer->endElement();

        return $writer->outputMemory();
    }
}
