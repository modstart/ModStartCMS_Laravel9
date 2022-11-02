<?php

namespace Sabre\CalDAV\Xml\Request;

use Sabre\CalDAV\Plugin;
use Sabre\Uri;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;


class CalendarMultiGetReport implements XmlDeserializable {

    
    public $properties;

    
    public $hrefs;

    
    public $expand = null;

    
    public $contentType = null;

    
    public $version = null;

    
    static function xmlDeserialize(Reader $reader) {

        $elems = $reader->parseInnerTree([
            '{urn:ietf:params:xml:ns:caldav}calendar-data' => 'Sabre\\CalDAV\\Xml\\Filter\\CalendarData',
            '{DAV:}prop'                                   => 'Sabre\\Xml\\Element\\KeyValue',
        ]);

        $newProps = [
            'hrefs'      => [],
            'properties' => [],
        ];

        foreach ($elems as $elem) {

            switch ($elem['name']) {

                case '{DAV:}prop' :
                    $newProps['properties'] = array_keys($elem['value']);
                    if (isset($elem['value']['{' . Plugin::NS_CALDAV . '}calendar-data'])) {
                        $newProps += $elem['value']['{' . Plugin::NS_CALDAV . '}calendar-data'];
                    }
                    break;
                case '{DAV:}href' :
                    $newProps['hrefs'][] = Uri\resolve($reader->contextUri, $elem['value']);
                    break;

            }

        }

        $obj = new self();
        foreach ($newProps as $key => $value) {
            $obj->$key = $value;
        }

        return $obj;

    }

}
