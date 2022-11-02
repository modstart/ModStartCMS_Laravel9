<?php

namespace Sabre\DAVACL\FS;

use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\FSExt\Directory as BaseCollection;
use Sabre\DAVACL\ACLTrait;
use Sabre\DAVACL\IACL;


class Collection extends BaseCollection implements IACL {

    use ACLTrait;

    
    protected $acl;

    
    protected $owner;

    
    function __construct($path, array $acl, $owner = null) {

        parent::__construct($path);
        $this->acl = $acl;
        $this->owner = $owner;

    }

    
    function getChild($name) {

        $path = $this->path . '/' . $name;

        if (!file_exists($path)) throw new NotFound('File could not be located');
        if ($name == '.' || $name == '..') throw new Forbidden('Permission denied to . and ..');

        if (is_dir($path)) {

            return new self($path, $this->acl, $this->owner);

        } else {

            return new File($path, $this->acl, $this->owner);

        }

    }

    
    function getOwner() {

        return $this->owner;

    }

    
    function getACL() {

        return $this->acl;

    }

}
