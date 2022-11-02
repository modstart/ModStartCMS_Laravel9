<?php

namespace Sabre\DAVACL;

use Sabre\DAV;
use Sabre\HTTP\URLUtil;


class Principal extends DAV\Node implements IPrincipal, DAV\IProperties, IACL {

    use ACLTrait;

    
    protected $principalProperties;

    
    protected $principalBackend;

    
    function __construct(PrincipalBackend\BackendInterface $principalBackend, array $principalProperties = []) {

        if (!isset($principalProperties['uri'])) {
            throw new DAV\Exception('The principal properties must at least contain the \'uri\' key');
        }
        $this->principalBackend = $principalBackend;
        $this->principalProperties = $principalProperties;

    }

    
    function getPrincipalUrl() {

        return $this->principalProperties['uri'];

    }

    
    function getAlternateUriSet() {

        $uris = [];
        if (isset($this->principalProperties['{DAV:}alternate-URI-set'])) {

            $uris = $this->principalProperties['{DAV:}alternate-URI-set'];

        }

        if (isset($this->principalProperties['{http://sabredav.org/ns}email-address'])) {
            $uris[] = 'mailto:' . $this->principalProperties['{http://sabredav.org/ns}email-address'];
        }

        return array_unique($uris);

    }

    
    function getGroupMemberSet() {

        return $this->principalBackend->getGroupMemberSet($this->principalProperties['uri']);

    }

    
    function getGroupMembership() {

        return $this->principalBackend->getGroupMemberShip($this->principalProperties['uri']);

    }

    
    function setGroupMemberSet(array $groupMembers) {

        $this->principalBackend->setGroupMemberSet($this->principalProperties['uri'], $groupMembers);

    }

    
    function getName() {

        $uri = $this->principalProperties['uri'];
        list(, $name) = URLUtil::splitPath($uri);
        return $name;

    }

    
    function getDisplayName() {

        if (isset($this->principalProperties['{DAV:}displayname'])) {
            return $this->principalProperties['{DAV:}displayname'];
        } else {
            return $this->getName();
        }

    }

    
    function getProperties($requestedProperties) {

        $newProperties = [];
        foreach ($requestedProperties as $propName) {

            if (isset($this->principalProperties[$propName])) {
                $newProperties[$propName] = $this->principalProperties[$propName];
            }

        }

        return $newProperties;

    }

    
    function propPatch(DAV\PropPatch $propPatch) {

        return $this->principalBackend->updatePrincipal(
            $this->principalProperties['uri'],
            $propPatch
        );

    }

    
    function getOwner() {

        return $this->principalProperties['uri'];


    }

}
