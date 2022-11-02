<?php

namespace Sabre\CardDAV\Xml\Request;

use Sabre\CardDAV\Plugin;
use Sabre\DAV\Exception\BadRequest;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;


class AddressBookQueryReport implements XmlDeserializable {

    
    public $properties;

    
    public $addressDataProperties = [];

    
    public $filters;

    
    public $limit;

    
    public $test;

    
    public $contentType = null;

    
    public $version = null;


    
    static function xmlDeserialize(Reader $reader) {

        $elems = (array)$reader->parseInnerTree([
            '{urn:ietf:params:xml:ns:carddav}prop-filter'  => 'Sabre\\CardDAV\\Xml\\Filter\\PropFilter',
            '{urn:ietf:params:xml:ns:carddav}param-filter' => 'Sabre\\CardDAV\\Xml\\Filter\\ParamFilter',
            '{urn:ietf:params:xml:ns:carddav}address-data' => 'Sabre\\CardDAV\\Xml\\Filter\\AddressData',
            '{DAV:}prop'                                   => 'Sabre\\Xml\\Element\\KeyValue',
        ]);

        $newProps = [
            'filters'    => null,
            'properties' => [],
            'test'       => 'anyof',
            'limit'      => null,
        ];

        if (!is_array($elems)) $elems = [];

        foreach ($elems as $elem) {

            switch ($elem['name']) {

                case '{DAV:}prop' :
                    $newProps['properties'] = array_keys($elem['value']);
                    if (isset($elem['value']['{' . Plugin::NS_CARDDAV . '}address-data'])) {
                        $newProps += $elem['value']['{' . Plugin::NS_CARDDAV . '}address-data'];
                    }
                    break;
                case '{' . Plugin::NS_CARDDAV . '}filter' :

                    if (!is_null($newProps['filters'])) {
                        throw new BadRequest('You can only include 1 {' . Plugin::NS_CARDDAV . '}filter element');
                    }
                    if (isset($elem['attributes']['test'])) {
                        $newProps['test'] = $elem['attributes']['test'];
                        if ($newProps['test'] !== 'allof' && $newProps['test'] !== 'anyof') {
                            throw new BadRequest('The "test" attribute must be one of "allof" or "anyof"');
                        }
                    }

                    $newProps['filters'] = [];
                    foreach ((array)$elem['value'] as $subElem) {
                        if ($subElem['name'] === '{' . Plugin::NS_CARDDAV . '}prop-filter') {
                            $newProps['filters'][] = $subElem['value'];
                        }
                    }
                    break;
                case '{' . Plugin::NS_CARDDAV . '}limit' :
                    foreach ($elem['value'] as $child) {
                        if ($child['name'] === '{' . Plugin::NS_CARDDAV . '}nresults') {
                            $newProps['limit'] = (int)$child['value'];
                        }
                    }
                    break;

            }

        }

        if (is_null($newProps['filters'])) {
            
                        $newProps['filters'] = [];

        }

        $obj = new self();
        foreach ($newProps as $key => $value) {
            $obj->$key = $value;
        }

        return $obj;

    }

}
