<?php

namespace Sabre\CalDAV\Xml\Filter;

use Sabre\CalDAV\Plugin;
use Sabre\DAV\Exception\BadRequest;
use Sabre\VObject\DateTimeParser;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;


class PropFilter implements XmlDeserializable {

    
    static function xmlDeserialize(Reader $reader) {

        $result = [
            'name'           => null,
            'is-not-defined' => false,
            'param-filters'  => [],
            'text-match'     => null,
            'time-range'     => false,
        ];

        $att = $reader->parseAttributes();
        $result['name'] = $att['name'];

        $elems = $reader->parseInnerTree();

        if (is_array($elems)) foreach ($elems as $elem) {

            switch ($elem['name']) {

                case '{' . Plugin::NS_CALDAV . '}param-filter' :
                    $result['param-filters'][] = $elem['value'];
                    break;
                case '{' . Plugin::NS_CALDAV . '}is-not-defined' :
                    $result['is-not-defined'] = true;
                    break;
                case '{' . Plugin::NS_CALDAV . '}time-range' :
                    $result['time-range'] = [
                        'start' => isset($elem['attributes']['start']) ? DateTimeParser::parseDateTime($elem['attributes']['start']) : null,
                        'end'   => isset($elem['attributes']['end']) ? DateTimeParser::parseDateTime($elem['attributes']['end']) : null,
                    ];
                    if ($result['time-range']['start'] && $result['time-range']['end'] && $result['time-range']['end'] <= $result['time-range']['start']) {
                        throw new BadRequest('The end-date must be larger than the start-date');
                    }
                    break;
                case '{' . Plugin::NS_CALDAV . '}text-match' :
                    $result['text-match'] = [
                        'negate-condition' => isset($elem['attributes']['negate-condition']) && $elem['attributes']['negate-condition'] === 'yes',
                        'collation'        => isset($elem['attributes']['collation']) ? $elem['attributes']['collation'] : 'i;ascii-casemap',
                        'value'            => $elem['value'],
                    ];
                    break;

            }

        }

        return $result;

    }

}
