<?php

namespace Sabre\CalDAV\Xml\Filter;

use Sabre\CalDAV\Plugin;
use Sabre\DAV\Exception\BadRequest;
use Sabre\VObject\DateTimeParser;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;


class CalendarData implements XmlDeserializable {

    
    static function xmlDeserialize(Reader $reader) {

        $result = [
            'contentType' => $reader->getAttribute('content-type') ?: 'text/calendar',
            'version'     => $reader->getAttribute('version') ?: '2.0',
        ];

        $elems = (array)$reader->parseInnerTree();
        foreach ($elems as $elem) {

            switch ($elem['name']) {
                case '{' . Plugin::NS_CALDAV . '}expand' :

                    $result['expand'] = [
                        'start' => isset($elem['attributes']['start']) ? DateTimeParser::parseDateTime($elem['attributes']['start']) : null,
                        'end'   => isset($elem['attributes']['end']) ? DateTimeParser::parseDateTime($elem['attributes']['end']) : null,
                    ];

                    if (!$result['expand']['start'] || !$result['expand']['end']) {
                        throw new BadRequest('The "start" and "end" attributes are required when expanding calendar-data');
                    }
                    if ($result['expand']['end'] <= $result['expand']['start']) {
                        throw new BadRequest('The end-date must be larger than the start-date when expanding calendar-data');
                    }
                    break;
            }

        }

        return $result;

    }

}
