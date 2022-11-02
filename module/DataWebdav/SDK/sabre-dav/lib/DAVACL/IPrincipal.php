<?php

namespace Sabre\DAVACL;

use Sabre\DAV;


interface IPrincipal extends DAV\INode {

    
    function getAlternateUriSet();

    
    function getPrincipalUrl();

    
    function getGroupMemberSet();

    
    function getGroupMembership();

    
    function setGroupMemberSet(array $principals);

    
    function getDisplayName();

}
