<?php

namespace Sabre\DAVACL\FS;

use Sabre\DAV\FSExt\File as BaseFile;
use Sabre\DAVACL\ACLTrait;
use Sabre\DAVACL\IACL;


class File extends BaseFile implements IACL {

    use ACLTrait;

    
    protected $acl;

    
    protected $owner;

    
    function __construct($path, array $acl, $owner = null) {

        parent::__construct($path);
        $this->acl = $acl;
        $this->owner = $owner;

    }

    
    function getOwner() {

        return $this->owner;

    }

    
    function getACL() {

        return $this->acl;

    }

}
