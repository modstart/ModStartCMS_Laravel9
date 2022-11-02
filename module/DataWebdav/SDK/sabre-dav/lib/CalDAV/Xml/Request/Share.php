<?php

namespace Sabre\CalDAV\Xml\Request;

use Sabre\CalDAV\Plugin;
use Sabre\DAV\Xml\Element\Sharee;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;


class Share implements XmlDeserializable {

    
    public $sharees = [];

    
    function __construct(array $sharees) {

        $this->sharees = $sharees;

    }

    
    static function xmlDeserialize(Reader $reader) {

        $elems = $reader->parseGetElements([
            '{' . Plugin::NS_CALENDARSERVER . '}set'    => 'Sabre\\Xml\\Element\\KeyValue',
            '{' . Plugin::NS_CALENDARSERVER . '}remove' => 'Sabre\\Xml\\Element\\KeyValue',
        ]);

        $sharees = [];

        foreach ($elems as $elem) {
            switch ($elem['name']) {

                case '{' . Plugin::NS_CALENDARSERVER . '}set' :
                    $sharee = $elem['value'];

                    $sumElem = '{' . Plugin::NS_CALENDARSERVER . '}summary';
                    $commonName = '{' . Plugin::NS_CALENDARSERVER . '}common-name';

                    $properties = [];
                    if (isset($sharee[$commonName])) {
                        $properties['{DAV:}displayname'] = $sharee[$commonName];
                    }

                    $access = array_key_exists('{' . Plugin::NS_CALENDARSERVER . '}read-write', $sharee)
                        ? \Sabre\DAV\Sharing\Plugin::ACCESS_READWRITE
                        : \Sabre\DAV\Sharing\Plugin::ACCESS_READ;

                    $sharees[] = new Sharee([
                        'href'       => $sharee['{DAV:}href'],
                        'properties' => $properties,
                        'access'     => $access,
                        'comment'    => isset($sharee[$sumElem]) ? $sharee[$sumElem] : null
                    ]);
                    break;

                case '{' . Plugin::NS_CALENDARSERVER . '}remove' :
                    $sharees[] = new Sharee([
                        'href'   => $elem['value']['{DAV:}href'],
                        'access' => \Sabre\DAV\Sharing\Plugin::ACCESS_NOACCESS
                    ]);
                    break;

            }
        }

        return new self($sharees);

    }

}
