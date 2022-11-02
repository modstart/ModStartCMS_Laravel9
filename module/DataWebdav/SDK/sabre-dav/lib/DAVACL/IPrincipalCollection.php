<?php

namespace Sabre\DAVACL;

use Sabre\DAV;


interface IPrincipalCollection extends DAV\ICollection {

    
    function searchPrincipals(array $searchProperties, $test = 'allof');

    
    function findByUri($uri);

}
