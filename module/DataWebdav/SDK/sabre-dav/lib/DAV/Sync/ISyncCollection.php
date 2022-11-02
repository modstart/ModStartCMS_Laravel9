<?php

namespace Sabre\DAV\Sync;

use Sabre\DAV;


interface ISyncCollection extends DAV\ICollection {

    
    function getSyncToken();

    
    function getChanges($syncToken, $syncLevel, $limit = null);

}
