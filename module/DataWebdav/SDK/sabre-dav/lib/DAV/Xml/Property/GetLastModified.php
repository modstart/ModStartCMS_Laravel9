<?php

namespace Sabre\DAV\Xml\Property;

use DateTime;
use DateTimeZone;
use Sabre\HTTP;
use Sabre\Xml\Element;
use Sabre\Xml\Reader;
use Sabre\Xml\Writer;


class GetLastModified implements Element {

    
    public $time;

    
    function __construct($time) {

        if ($time instanceof DateTime) {
            $this->time = clone $time;
        } else {
            $this->time = new DateTime('@' . $time);
        }

                $this->time->setTimezone(new DateTimeZone('UTC'));

    }

    
    function getTime() {

        return $this->time;

    }

    
    function xmlSerialize(Writer $writer) {

        $writer->write(
            HTTP\Util::toHTTPDate($this->time)
        );

    }

    
    static function xmlDeserialize(Reader $reader) {

        return
            new self(new DateTime($reader->parseInnerTree()));

    }
}
