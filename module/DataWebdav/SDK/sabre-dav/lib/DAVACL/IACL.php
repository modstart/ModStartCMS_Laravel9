<?php

namespace Sabre\DAVACL;

use Sabre\DAV;


interface IACL extends DAV\INode {

    
    function getOwner();

    
    function getGroup();

    
    function getACL();

    
    function setACL(array $acl);

    
    function getSupportedPrivilegeSet();

}
