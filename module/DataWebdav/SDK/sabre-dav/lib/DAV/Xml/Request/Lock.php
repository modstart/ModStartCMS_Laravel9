<?php

namespace Sabre\DAV\Xml\Request;

use Sabre\DAV\Locks\LockInfo;
use Sabre\Xml\Element\KeyValue;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;


class Lock implements XmlDeserializable {

    
    public $owner;

    
    public $scope;

    
    static function xmlDeserialize(Reader $reader) {

        $reader->pushContext();
        $reader->elementMap['{DAV:}owner'] = 'Sabre\\Xml\\Element\\XmlFragment';

        $values = KeyValue::xmlDeserialize($reader);

        $reader->popContext();

        $new = new self();
        $new->owner = !empty($values['{DAV:}owner']) ? $values['{DAV:}owner']->getXml() : null;
        $new->scope = LockInfo::SHARED;

        if (isset($values['{DAV:}lockscope'])) {
            foreach ($values['{DAV:}lockscope'] as $elem) {
                if ($elem['name'] === '{DAV:}exclusive') $new->scope = LockInfo::EXCLUSIVE;
            }
        }
        return $new;

    }

}
