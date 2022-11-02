<?php

namespace Sabre\CalDAV\Principal;

use Sabre\DAV;
use Sabre\DAVACL;


class ProxyRead implements IProxyRead {

    
    protected $principalInfo;

    
    protected $principalBackend;

    
    function __construct(DAVACL\PrincipalBackend\BackendInterface $principalBackend, array $principalInfo) {

        $this->principalInfo = $principalInfo;
        $this->principalBackend = $principalBackend;

    }

    
    function getName() {

        return 'calendar-proxy-read';

    }

    
    function getLastModified() {

        return null;

    }

    
    function delete() {

        throw new DAV\Exception\Forbidden('Permission denied to delete node');

    }

    
    function setName($name) {

        throw new DAV\Exception\Forbidden('Permission denied to rename file');

    }


    
    function getAlternateUriSet() {

        return [];

    }

    
    function getPrincipalUrl() {

        return $this->principalInfo['uri'] . '/' . $this->getName();

    }

    
    function getGroupMemberSet() {

        return $this->principalBackend->getGroupMemberSet($this->getPrincipalUrl());

    }

    
    function getGroupMembership() {

        return $this->principalBackend->getGroupMembership($this->getPrincipalUrl());

    }

    
    function setGroupMemberSet(array $principals) {

        $this->principalBackend->setGroupMemberSet($this->getPrincipalUrl(), $principals);

    }

    
    function getDisplayName() {

        return $this->getName();

    }

}
