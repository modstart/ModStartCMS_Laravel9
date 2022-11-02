<?php

namespace Sabre\CardDAV\Xml\Property;

use Sabre\CardDAV\Plugin;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;


class SupportedAddressData implements XmlSerializable {

    
    protected $supportedData = [];

    
    function __construct(array $supportedData = null) {

        if (is_null($supportedData)) {
            $supportedData = [
                ['contentType' => 'text/vcard', 'version' => '3.0'],
                ['contentType' => 'text/vcard', 'version' => '4.0'],
                ['contentType' => 'application/vcard+json', 'version' => '4.0'],
            ];
        }

        $this->supportedData = $supportedData;

    }

    
    function xmlSerialize(Writer $writer) {

        foreach ($this->supportedData as $supported) {
            $writer->startElement('{' . Plugin::NS_CARDDAV . '}address-data-type');
            $writer->writeAttributes([
                'content-type' => $supported['contentType'],
                'version'      => $supported['version']
                ]);
            $writer->endElement();         }

    }

}
