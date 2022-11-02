<?php

namespace Sabre\DAVACL\PrincipalBackend;


interface BackendInterface {

    
    function getPrincipalsByPrefix($prefixPath);

    
    function getPrincipalByPath($path);

    
    function updatePrincipal($path, \Sabre\DAV\PropPatch $propPatch);

    
    function searchPrincipals($prefixPath, array $searchProperties, $test = 'allof');

    
    function findByUri($uri, $principalPrefix);

    
    function getGroupMemberSet($principal);

    
    function getGroupMembership($principal);

    
    function setGroupMemberSet($principal, array $members);

}
