<?php

namespace Sabre\DAVACL\FS;

use Sabre\DAVACL\AbstractPrincipalCollection;
use Sabre\DAVACL\ACLTrait;
use Sabre\DAVACL\IACL;
use Sabre\DAVACL\PrincipalBackend\BackendInterface;
use Sabre\Uri;


class HomeCollection extends AbstractPrincipalCollection implements IACL {

    use ACLTrait;

    
    public $collectionName = 'home';

    
    protected $storagePath;

    
    function __construct(BackendInterface $principalBackend, $storagePath, $principalPrefix = 'principals') {

        parent::__construct($principalBackend, $principalPrefix);
        $this->storagePath = $storagePath;

    }

    
    function getName() {

        return $this->collectionName;

    }

    
    function getChildForPrincipal(array $principalInfo) {

        $owner = $principalInfo['uri'];
        $acl = [
            [
                'privilege' => '{DAV:}all',
                'principal' => '{DAV:}owner',
                'protected' => true,
            ],
        ];

        list(, $principalBaseName) = Uri\split($owner);

        $path = $this->storagePath . '/' . $principalBaseName;

        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        return new Collection(
            $path,
            $acl,
            $owner
        );

    }


    
    function getACL() {

        return [
            [
                'principal' => '{DAV:}authenticated',
                'privilege' => '{DAV:}read',
                'protected' => true,
            ]
        ];

    }

}
