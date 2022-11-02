<?php

namespace Sabre\CardDAV\Xml\Filter;

use Sabre\CardDAV\Plugin;
use Sabre\DAV\Exception\BadRequest;
use Sabre\Xml\Element;
use Sabre\Xml\Reader;


abstract class ParamFilter implements Element {

    
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

                case '{' . Plugin::NS_CARDDAV . '}is-not-defined' :
                    $result['is-not-defined'] = true;
                    break;
                case '{' . Plugin::NS_CARDDAV . '}text-match' :
                    $matchType = isset($elem['attributes']['match-type']) ? $elem['attributes']['match-type'] : 'contains';

                    if (!in_array($matchType, ['contains', 'equals', 'starts-with', 'ends-with'])) {
                        throw new BadRequest('Unknown match-type: ' . $matchType);
                    }
                    $result['text-match'] = [
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
