<?php

namespace Sabre\DAV\Xml\Element;

use Sabre\Xml\Element;
use Sabre\Xml\Reader;
use Sabre\Xml\Writer;


class Response implements Element {

    
    protected $href;

    
    protected $responseProperties;

    
    protected $httpStatus;

    
    function __construct($href, array $responseProperties, $httpStatus = null) {

        $this->href = $href;
        $this->responseProperties = $responseProperties;
        $this->httpStatus = $httpStatus;

    }

    
    function getHref() {

        return $this->href;

    }

    
    function getHttpStatus() {

        return $this->httpStatus;

    }

    
    function getResponseProperties() {

        return $this->responseProperties;

    }


    
    function xmlSerialize(Writer $writer) {

        if ($status = $this->getHTTPStatus()) {
            $writer->writeElement('{DAV:}status', 'HTTP/1.1 ' . $status . ' ' . \Sabre\HTTP\Response::$statusCodes[$status]);
        }
        $writer->writeElement('{DAV:}href', $writer->contextUri . \Sabre\HTTP\encodePath($this->getHref()));

        $empty = true;

        foreach ($this->getResponseProperties() as $status => $properties) {

                        if (!$properties || (!ctype_digit($status) && !is_int($status))) {
                continue;
            }
            $empty = false;
            $writer->startElement('{DAV:}propstat');
            $writer->writeElement('{DAV:}prop', $properties);
            $writer->writeElement('{DAV:}status', 'HTTP/1.1 ' . $status . ' ' . \Sabre\HTTP\Response::$statusCodes[$status]);
            $writer->endElement(); 
        }
        if ($empty) {
            
            $writer->writeElement('{DAV:}propstat', [
                '{DAV:}prop'   => [],
                '{DAV:}status' => 'HTTP/1.1 418 ' . \Sabre\HTTP\Response::$statusCodes[418]
            ]);

        }

    }

    
    static function xmlDeserialize(Reader $reader) {

        $reader->pushContext();

        $reader->elementMap['{DAV:}propstat'] = 'Sabre\\Xml\\Element\\KeyValue';

                                                                        $reader->elementMap['{DAV:}prop'] = function(Reader $reader) {

            if ($reader->isEmptyElement) {
                $reader->next();
                return [];
            }
            $values = [];
            $reader->read();
            do {
                if ($reader->nodeType === Reader::ELEMENT) {
                    $clark = $reader->getClark();

                    if ($reader->isEmptyElement) {
                        $values[$clark] = null;
                        $reader->next();
                    } else {
                        $values[$clark] = $reader->parseCurrentElement()['value'];
                    }
                } else {
                    $reader->read();
                }
            } while ($reader->nodeType !== Reader::END_ELEMENT);
            $reader->read();
            return $values;

        };
        $elems = $reader->parseInnerTree();
        $reader->popContext();

        $href = null;
        $propertyLists = [];
        $statusCode = null;

        foreach ($elems as $elem) {

            switch ($elem['name']) {

                case '{DAV:}href' :
                    $href = $elem['value'];
                    break;
                case '{DAV:}propstat' :
                    $status = $elem['value']['{DAV:}status'];
                    list(, $status, ) = explode(' ', $status, 3);
                    $properties = isset($elem['value']['{DAV:}prop']) ? $elem['value']['{DAV:}prop'] : [];
                    if ($properties) $propertyLists[$status] = $properties;
                    break;
                case '{DAV:}status' :
                    list(, $statusCode, ) = explode(' ', $elem['value'], 3);
                    break;

            }

        }

        return new self($href, $propertyLists, $statusCode);

    }

}
