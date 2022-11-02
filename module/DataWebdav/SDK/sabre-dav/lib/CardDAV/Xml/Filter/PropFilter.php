<?php

namespace Sabre\CardDAV\Xml\Filter;

use Sabre\CardDAV\Plugin;
use Sabre\DAV\Exception\BadRequest;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;


class PropFilter implements XmlDeserializable {

    
    static function xmlDeserialize(Reader $reader) {

        $result = [
            'name'           => null,
            'test'           => 'anyof',
            'is-not-defined' => false,
            'param-filters'  => [],
            'text-matches'   => [],
        ];

        $att = $reader->parseAttributes();
        $result['name'] = $att['name'];

        if (isset($att['test']) && $att['test'] === 'allof') {
            $result['test'] = 'allof';
        }

        $elems = $reader->parseInnerTree();

        if (is_array($elems)) foreach ($elems as $elem) {

            switch ($elem['name']) {

                case '{' . Plugin::NS_CARDDAV . '}param-filter' :
                    $result['param-filters'][] = $elem['value'];
                    break;
                case '{' . Plugin::NS_CARDDAV . '}is-not-defined' :
                    $result['is-not-defined'] = true;
                    break;
                case '{' . Plugin::NS_CARDDAV . '}text-match' :
                    $matchType = isset($elem['attributes']['match-type']) ? $elem['attributes']['match-type'] : 'contains';

                    if (!in_array($matchType, ['contains', 'equals', 'starts-with', 'ends-with'])) {
                        throw new BadRequest('Unknown match-type: ' . $matchType);
                    }
                    $result['text-matches'][] = [
                        'negate-condition' => isset($elem['attributes']['negate-condition']) && $elem['attributes']['negate-condition'] === 'yes',
                        'collation'        => isset($elem['attributes']['collation']) ? $elem['attributes']['collation'] : 'i;unicode-casemap',
                        'value'            => $elem['value'],
                        'match-type'       => $matchType,
                    ];
                    break;

            }

        }

        return $result;

    }

}
