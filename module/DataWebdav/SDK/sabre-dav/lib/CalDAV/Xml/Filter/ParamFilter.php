<?php

namespace Sabre\CalDAV\Xml\Filter;

use Sabre\CalDAV\Plugin;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;


class ParamFilter implements XmlDeserializable {

    
    static function xmlDeserialize(Reader $reader) {

        $result = [
            'name'           => null,
            'is-not-defined' => false,
            'text-match'     => null,
        ];

        $att = $reader->parseAttributes();
        $result['name'] = $att['name'];

        $elems = $reader->parseInnerTree();

        if (is_array($elems)) foreach ($elems as $elem) {

            switch ($elem['name']) {

                case '{' . Plugin::NS_CALDAV . '}is-not-defined' :
                    $result['is-not-defined'] = true;
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
