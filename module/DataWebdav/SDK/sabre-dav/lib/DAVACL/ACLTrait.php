<?php

namespace Sabre\DAVACL;


trait ACLTrait {

    
    function getOwner() {

        return null;

    }

    
    function getGroup() {

        return null;

    }

    
    function getACL() {

        return [
            [
                'privilege' => '{DAV:}all',
                'principal' => '{DAV:}owner',
                'protected' => true,
            ]
        ];

    }

    
    function setACL(array $acl) {

        throw new \Sabre\DAV\Exception\Forbidden('Setting ACL is not supported on this node');
    }

    
    function getSupportedPrivilegeSet() {

        return null;

    }

}
