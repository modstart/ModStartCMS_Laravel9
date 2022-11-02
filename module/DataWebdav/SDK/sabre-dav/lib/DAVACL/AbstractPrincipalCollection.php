<?php

namespace Sabre\DAVACL;

use Sabre\DAV;
use Sabre\HTTP\URLUtil;


abstract class AbstractPrincipalCollection extends DAV\Collection implements IPrincipalCollection {

    
    protected $principalBackend;

    
    protected $principalPrefix;

    
    public $disableListing = false;

    
    function __construct(PrincipalBackend\BackendInterface $principalBackend, $principalPrefix = 'principals') {

        $this->principalPrefix = $principalPrefix;
        $this->principalBackend = $principalBackend;

    }

    
    abstract function getChildForPrincipal(array $principalInfo);

    
    function getName() {

        list(, $name) = URLUtil::splitPath($this->principalPrefix);
        return $name;

    }

    
    function getChildren() {

        if ($this->disableListing)
            throw new DAV\Exception\MethodNotAllowed('Listing members of this collection is disabled');

        $children = [];
        foreach ($this->principalBackend->getPrincipalsByPrefix($this->principalPrefix) as $principalInfo) {

            $children[] = $this->getChildForPrincipal($principalInfo);


        }
        return $children;

    }

    
    function getChild($name) {

        $principalInfo = $this->principalBackend->getPrincipalByPath($this->principalPrefix . '/' . $name);
        if (!$principalInfo) throw new DAV\Exception\NotFound('Principal with name ' . $name . ' not found');
        return $this->getChildForPrincipal($principalInfo);

    }

    
    function searchPrincipals(array $searchProperties, $test = 'allof') {

        $result = $this->principalBackend->searchPrincipals($this->principalPrefix, $searchProperties, $test);
        $r = [];

        foreach ($result as $row) {
            list(, $r[]) = URLUtil::splitPath($row);
        }

        return $r;

    }

    
    function findByUri($uri) {

        return $this->principalBackend->findByUri($uri, $this->principalPrefix);

    }

}
