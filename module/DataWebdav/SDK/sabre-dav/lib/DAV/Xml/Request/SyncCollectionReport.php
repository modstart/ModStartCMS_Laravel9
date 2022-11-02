<?php

namespace Sabre\DAV\Xml\Request;

use Sabre\DAV\Exception\BadRequest;
use Sabre\Xml\Element\KeyValue;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;


class SyncCollectionReport implements XmlDeserializable {

    
    public $syncToken;

    
    public $syncLevel;

    
    public $limit;

    
    public $properties;

    
    static function xmlDeserialize(Reader $reader) {

        $self = new self();

        $reader->pushContext();

        $reader->elementMap['{DAV:}prop'] = 'Sabre\Xml\Element\Elements';
        $elems = KeyValue::xmlDeserialize($reader);

        $reader->popContext();

        $required = [
            '{DAV:}sync-token',
            '{DAV:}prop',
            ];

        foreach ($required as $elem) {
            if (!array_key_exists($elem, $elems)) {
                throw new BadRequest('The ' . $elem . ' element in the {DAV:}sync-collection report is required');
            }
        }


        $self->properties = $elems['{DAV:}prop'];
        $self->syncToken = $elems['{DAV:}sync-token'];

        if (isset($elems['{DAV:}limit'])) {
            $nresults = null;
            foreach ($elems['{DAV:}limit'] as $child) {
                if ($child['name'] === '{DAV:}nresults') {
                    $nresults = (int)$child['value'];
                }
            }
            $self->limit = $nresults;
        }

        if (isset($elems['{DAV:}sync-level'])) {

            $value = $elems['{DAV:}sync-level'];
            if ($value === 'infinity') {
                $value = \Sabre\DAV\Server::DEPTH_INFINITY;
            }
            $self->syncLevel = $value;

        }

        return $self;

    }

}
