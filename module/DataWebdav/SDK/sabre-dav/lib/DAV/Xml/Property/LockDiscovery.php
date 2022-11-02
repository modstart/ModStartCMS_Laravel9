<?php

namespace Sabre\DAV\Xml\Property;

use Sabre\DAV;
use Sabre\DAV\Locks\LockInfo;
use Sabre\Xml\Element\XmlFragment;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;


class LockDiscovery implements XmlSerializable {

    
    public $locks;

    
    static $hideLockRoot = false;

    
    function __construct($locks) {

        $this->locks = $locks;

    }

    
    function xmlSerialize(Writer $writer) {

        foreach ($this->locks as $lock) {

            $writer->startElement('{DAV:}activelock');

            $writer->startElement('{DAV:}lockscope');
            if ($lock->scope === LockInfo::SHARED) {
                $writer->writeElement('{DAV:}shared');
            } else {
                $writer->writeElement('{DAV:}exclusive');
            }

            $writer->endElement(); 
            $writer->startElement('{DAV:}locktype');
            $writer->writeElement('{DAV:}write');
            $writer->endElement(); 
            if (!self::$hideLockRoot) {
                $writer->startElement('{DAV:}lockroot');
                $writer->writeElement('{DAV:}href', $writer->contextUri . $lock->uri);
                $writer->endElement();             }
            $writer->writeElement('{DAV:}depth', ($lock->depth == DAV\Server::DEPTH_INFINITY ? 'infinity' : $lock->depth));
            $writer->writeElement('{DAV:}timeout', 'Second-' . $lock->timeout);

            $writer->startElement('{DAV:}locktoken');
            $writer->writeElement('{DAV:}href', 'opaquelocktoken:' . $lock->token);
            $writer->endElement(); 
            $writer->writeElement('{DAV:}owner', new XmlFragment($lock->owner));
            $writer->endElement(); 
        }

    }

}
