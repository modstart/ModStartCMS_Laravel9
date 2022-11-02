<?php

namespace Sabre\CalDAV\Xml\Filter;

use Sabre\CalDAV\Plugin;
use Sabre\DAV\Exception\BadRequest;
use Sabre\VObject\DateTimeParser;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;


class CompFilter implements XmlDeserializable {

    
    static function xmlDeserialize(Reader $reader) {

        $result = [
            'name'           => null,
            'is-not-defined' => false,
            'comp-filters'   => [],
            'prop-filters'   => [],
            'time-range'     => false,
        ];

        $att = $reader->parseAttributes();
        $result['name'] = $att['name'];

        $elems = $reader->parseInnerTree();

        if (is_array($elems)) foreach ($elems as $elem) {

            switch ($elem['name']) {

                case '{' . Plugin::NS_CALDAV . '}comp-filter' :
                    $result['comp-filters'][] = $elem['value'];
                    break;
                case '{' . Plugin::NS_CALDAV . '}prop-filter' :
                    $result['prop-filters'][] = $elem['value'];
                    break;
                case '{' . Plugin::NS_CALDAV . '}is-not-defined' :
                    $result['is-not-defined'] = true;
                    break;
                case '{' . Plugin::NS_CALDAV . '}time-range' :
                    if ($result['name'] === 'VCALENDAR') {
                        throw new BadRequest('You cannot add time-range filters on the VCALENDAR component');
                    }
                    $result['time-range'] = [
                        'start' => isset($elem['attributes']['start']) ? DateTimeParser::parseDateTime($elem['attributes']['start']) : null,
                        'end'   => isset($elem['attributes']['end']) ? DateTimeParser::parseDateTime($elem['attributes']['end']) : null,
                    ];
                    if ($result['time-range']['start'] && $result['time-range']['end'] && $result['time-range']['end'] <= $result['time-range']['start']) {
                        throw new BadRequest('The end-date must be larger than the start-date');
                    }
                    break;

            }

        }

        return $result;

    }

}
