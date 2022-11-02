<?php

namespace Sabre\DAV\Xml\Response;

use Sabre\Xml\Element;
use Sabre\Xml\Reader;
use Sabre\Xml\Writer;


class MultiStatus implements Element {

    
    protected $responses;

    
    protected $syncToken;

    
    function __construct(array $responses, $syncToken = null) {

        $this->responses = $responses;
        $this->syncToken = $syncToken;

    }

    
    function getResponses() {

        return $this->responses;

    }

    
    function getSyncToken() {

        return $this->syncToken;

    }

    
    function xmlSerialize(Writer $writer) {

        foreach ($this->getResponses() as $response) {
            $writer->writeElement('{DAV:}response', $response);
        }
        if ($syncToken = $this->getSyncToken()) {
            $writer->writeElement('{DAV:}sync-token', $syncToken);
        }

    }

    
    static function xmlDeserialize(Reader $reader) {

        $elementMap = $reader->elementMap;
        $elementMap['{DAV:}prop'] = 'Sabre\\DAV\\Xml\\Element\\Prop';
        $elements = $reader->parseInnerTree($elementMap);

        $responses = [];
        $syncToken = null;

        if ($elements) foreach ($elements as $elem) {
            if ($elem['name'] === '{DAV:}response') {
                $responses[] = $elem['value'];
            }
            if ($elem['name'] === '{DAV:}sync-token') {
                $syncToken = $elem['value'];
            }
        }

        return new self($responses, $syncToken);

    }

}
