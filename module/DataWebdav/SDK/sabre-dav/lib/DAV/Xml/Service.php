<?php

namespace Sabre\DAV\Xml;


class Service extends \Sabre\Xml\Service {

    
    public $elementMap = [
        '{DAV:}multistatus' => 'Sabre\\DAV\\Xml\\Response\\MultiStatus',
        '{DAV:}response'    => 'Sabre\\DAV\\Xml\\Element\\Response',

                '{DAV:}propfind'       => 'Sabre\\DAV\\Xml\\Request\\PropFind',
        '{DAV:}propertyupdate' => 'Sabre\\DAV\\Xml\\Request\\PropPatch',
        '{DAV:}mkcol'          => 'Sabre\\DAV\\Xml\\Request\\MkCol',

                '{DAV:}resourcetype' => 'Sabre\\DAV\\Xml\\Property\\ResourceType',

    ];

    
    public $namespaceMap = [
        'DAV:'                   => 'd',
        'http://sabredav.org/ns' => 's',
    ];

}
