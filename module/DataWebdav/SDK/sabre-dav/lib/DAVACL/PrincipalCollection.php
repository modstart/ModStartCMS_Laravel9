<?php

namespace Sabre\DAVACL;

use Sabre\DAV\Exception\InvalidResourceType;
use Sabre\DAV\IExtendedCollection;
use Sabre\DAV\MkCol;


class PrincipalCollection extends AbstractPrincipalCollection implements IExtendedCollection, IACL {

    use ACLTrait;

    
    function getChildForPrincipal(array $principal) {

        return new Principal($this->principalBackend, $principal);

    }

    
    function createExtendedCollection($name, MkCol $mkCol) {

        if (!$mkCol->hasResourceType('{DAV:}principal')) {
            throw new InvalidResourceType('Only resources of type {DAV:}principal may be created here');
        }

        $this->principalBackend->createPrincipal(
            $this->principalPrefix . '/' . $name,
            $mkCol
        );

    }

    
    function getACL() {
        return [
            [
                'principal' => '{DAV:}authenticated',
                'privilege' => '{DAV:}read',
                'protected' => true,
            ],
        ];
    }

}
