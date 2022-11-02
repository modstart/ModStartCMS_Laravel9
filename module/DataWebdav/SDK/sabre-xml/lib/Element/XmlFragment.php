<?php

namespace Sabre\Xml\Element;

use Sabre\Xml\Element;
use Sabre\Xml\Reader;
use Sabre\Xml\Writer;


class XmlFragment implements Element {

    protected $xml;

    function __construct($xml) {

        $this->xml = $xml;

    }

    function getXml() {

        return $this->xml;

    }

    
    function xmlSerialize(Writer $writer) {

        $reader = new Reader();

                        $xml = <<<XML
<?xml version="1.0"?>
<xml-fragment xmlns="http://sabre.io/ns">{$this->getXml()}</xml-fragment>
XML;

        $reader->xml($xml);

        while ($reader->read()) {

            if ($reader->depth < 1) {
                                continue;
            }

            switch ($reader->nodeType) {

                case Reader::ELEMENT :
                    $writer->startElement(
                        $reader->getClark()
                    );
                    $empty = $reader->isEmptyElement;
                    while ($reader->moveToNextAttribute()) {
                        switch ($reader->namespaceURI) {
                            case '' :
                                $writer->writeAttribute($reader->localName, $reader->value);
                                break;
                            case 'http://www.w3.org/2000/xmlns/' :
                                                                break;
                            default :
                                $writer->writeAttribute($reader->getClark(), $reader->value);
                                break;
                        }
                    }
                    if ($empty) {
                        $writer->endElement();
                    }
                    break;
                case Reader::CDATA :
                case Reader::TEXT :
                    $writer->text(
                        $reader->value
                    );
                    break;
                case Reader::END_ELEMENT :
                    $writer->endElement();
                    break;

            }

        }

    }

    
    static function xmlDeserialize(Reader $reader) {

        $result = new self($reader->readInnerXml());
        $reader->next();
        return $result;

    }

}
