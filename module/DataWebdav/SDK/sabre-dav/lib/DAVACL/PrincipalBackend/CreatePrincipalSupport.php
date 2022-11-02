<?php

namespace Sabre\DAVACL\PrincipalBackend;

use Sabre\DAV\MkCol;


interface CreatePrincipalSupport extends BackendInterface {

    
    function createPrincipal($path, MkCol $mkCol);

}
