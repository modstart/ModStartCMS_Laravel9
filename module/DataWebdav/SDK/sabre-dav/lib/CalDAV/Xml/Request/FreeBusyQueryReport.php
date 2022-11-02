<?php

namespace Sabre\CalDAV\Xml\Request;

use Sabre\CalDAV\Plugin;
use Sabre\DAV\Exception\BadRequest;
use Sabre\VObject\DateTimeParser;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;


class FreeBusyQueryReport implements XmlDeserializable {

    
    public $start;

    
    public $end;

    
    static function xmlDeserialize(Reader $reader) {

        $timeRange = '{' . Plugin::NS_CALDAV . '}time-range';

        $start = null;
        $end = null;

        foreach ((array)$reader->parseInnerTree([]) as $elem) {

            if ($elem['name'] !== $timeRange) continue;

            $start = empty($elem['attributes']['start']) ?: $elem['attributes']['start'];
            $end = empty($elem['attributes']['end']) ?: $elem['attributes']['end'];

        }
        if (!$start && !$end) {
            throw new BadRequest('The freebusy report must have a time-range element');
        }
        if ($start) {
            $start = DateTimeParser::parseDateTime($start);
        }
        if ($end) {
            $end = DateTimeParser::parseDateTime($end);
        }
        $result = new self();
        $result->start = $start;
        $result->end = $end;

        return $result;

    }

}
