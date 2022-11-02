<?php

namespace Sabre\CalDAV\Principal;

use Sabre\DAV;
use Sabre\DAVACL;


class User extends DAVACL\Principal implements DAV\ICollection {

    
    function createFile($name, $data = null) {

        throw new DAV\Exception\Forbidden('Permission denied to create file (filename ' . $name . ')');

    }

    
    function createDirectory($name) {

        throw new DAV\Exception\Forbidden('Permission denied to create directory');

    }

    
    function getChild($name) {

        $principal = $this->principalBackend->getPrincipalByPath($this->getPrincipalURL() . '/' . $name);
        if (!$principal) {
            throw new DAV\Exception\NotFound('Node with name ' . $name . ' was not found');
        }
        if ($name === 'calendar-proxy-read')
            return new ProxyRead($this->principalBackend, $this->principalProperties);

        if ($name === 'calendar-proxy-write')
            return new ProxyWrite($this->principalBackend, $this->principalProperties);

        throw new DAV\Exception\NotFound('Node with name ' . $name . ' was not found');

    }

    
    function getChildren() {

        $r = [];
        if ($this->principalBackend->getPrincipalByPath($this->getPrincipalURL() . '/calendar-proxy-read')) {
            $r[] = new ProxyRead($this->principalBackend, $this->principalProperties);
        }
        if ($this->principalBackend->getPrincipalByPath($this->getPrincipalURL() . '/calendar-proxy-write')) {
            $r[] = new ProxyWrite($this->principalBackend, $this->principalProperties);
        }

        return $r;

    }

    
    function childExists($name) {

        try {
            $this->getChild($name);
            return true;
        } catch (DAV\Exception\NotFound $e) {
            return false;
        }

    }

    
    function getACL() {

        $acl = parent::getACL();
        $acl[] = [
            'privilege' => '{DAV:}read',
            'principal' => $this->principalProperties['uri'] . '/calendar-proxy-read',
            'protected' => true,
        ];
        $acl[] = [
            'privilege' => '{DAV:}read',
            'principal' => $this->principalProperties['uri'] . '/calendar-proxy-write',
            'protected' => true,
        ];
        return $acl;

    }

}
