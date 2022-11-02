<?php

namespace Sabre\CalDAV\Xml\Request;

use Sabre\CalDAV\Plugin;
use Sabre\DAV\Exception\BadRequest;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;


class CalendarQueryReport implements XmlDeserializable {

    
    public $properties;

    
    public $filters;

    
    public $expand = null;

    
    public $contentType = null;

    
    public $version = null;

    
    static function xmlDeserialize(Reader $reader) {

        $elems = $reader->parseInnerTree([
            '{urn:ietf:params:xml:ns:caldav}comp-filter'   => 'Sabre\\CalDAV\\Xml\\Filter\\CompFilter',
            '{urn:ietf:params:xml:ns:caldav}prop-filter'   => 'Sabre\\CalDAV\\Xml\\Filter\\PropFilter',
            '{urn:ietf:params:xml:ns:caldav}param-filter'  => 'Sabre\\CalDAV\\Xml\\Filter\\ParamFilter',
            '{urn:ietf:params:xml:ns:caldav}calendar-data' => 'Sabre\\CalDAV\\Xml\\Filter\\CalendarData',
            '{DAV:}prop'                                   => 'Sabre\\Xml\\Element\\KeyValue',
        ]);

        $newProps = [
            'filters'    => null,
            'properties' => [],
        ];

        if (!is_array($elems)) $elems = [];

        foreach ($elems as $elem) {

            switch ($elem['name']) {

                case '{DAV:}prop' :
                    $newProps['properties'] = array_keys($elem['value']);
                    if (isset($elem['value']['{' . Plugin::NS_CALDAV . '}calendar-data'])) {
                        $newProps += $elem['value']['{' . Plugin::NS_CALDAV . '}calendar-data'];
                    }
                    break;
                case '{' . Plugin::NS_CALDAV . '}filter' :
                    foreach ($elem['value'] as $subElem) {
                        if ($subElem['name'] === '{' . Plugin::NS_CALDAV . '}comp-filter') {
                            if (!is_null($newProps['filters'])) {
                                throw new BadRequest('Only one top-level comp-filter may be defined');
                            }
                            $newProps['filters'] = $subElem['value'];
                        }
                    }
                    break;

            }

        }

        if (is_null($newProps['filters'])) {
            throw new BadRequest('The {' . Plugin::NS_CALDAV . '}filter element is required for this request');
        }

        $obj = new self();
        foreach ($newProps as $key => $value) {
            $obj->$key = $value;
        }
        return $obj;

    }

}
