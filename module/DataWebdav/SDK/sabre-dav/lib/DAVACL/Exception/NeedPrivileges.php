<?php

namespace Sabre\DAVACL\Exception;

use Sabre\DAV;


class NeedPrivileges extends DAV\Exception\Forbidden {

    
    protected $uri;

    
    protected $privileges;

    
    function __construct($uri, array $privileges) {

        $this->uri = $uri;
        $this->privileges = $privileges;

        parent::__construct('User did not have the required privileges (' . implode(',', $privileges) . ') for path "' . $uri . '"');

    }

    
    function serialize(DAV\Server $server, \DOMElement $errorNode) {

        $doc = $errorNode->ownerDocument;

        $np = $doc->createElementNS('DAV:', 'd:need-privileges');
        $errorNode->appendChild($np);

        foreach ($this->privileges as $privilege) {

            $resource = $doc->createElementNS('DAV:', 'd:resource');
            $np->appendChild($resource);

            $resource->appendChild($doc->createElementNS('DAV:', 'd:href', $server->getBaseUri() . $this->uri));

            $priv = $doc->createElementNS('DAV:', 'd:privilege');
            $resource->appendChild($priv);

            preg_match('/^{([^}]*)}(.*)$/', $privilege, $privilegeParts);
            $priv->appendChild($doc->createElementNS($privilegeParts[1], 'd:' . $privilegeParts[2]));


        }

    }

}
