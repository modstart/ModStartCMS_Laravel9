<?php

namespace Sabre\CalDAV\Principal;

use Sabre\DAVACL;


class Collection extends DAVACL\PrincipalCollection {

    
    function getChildForPrincipal(array $principalInfo) {

        return new User($this->principalBackend, $principalInfo);

    }

}
