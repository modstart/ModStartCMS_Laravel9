<?php

namespace Sabre\DAV\Xml\Property;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;


class SupportedLock implements XmlSerializable {

    
    function xmlSerialize(Writer $writer) {

        $writer->writeElement('{DAV:}lockentry', [
            '{DAV:}lockscope' => ['{DAV:}exclusive' => null],
            '{DAV:}locktype'  => ['{DAV:}write' => null],
        ]);
        $writer->writeElement('{DAV:}lockentry', [
            '{DAV:}lockscope' => ['{DAV:}shared' => null],
            '{DAV:}locktype'  => ['{DAV:}write' => null],
        ]);

    }
}
