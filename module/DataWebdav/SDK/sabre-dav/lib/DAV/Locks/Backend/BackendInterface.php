<?php

namespace Sabre\DAV\Locks\Backend;

use Sabre\DAV\Locks;


interface BackendInterface {

    
    function getLocks($uri, $returnChildLocks);

    
    function lock($uri, Locks\LockInfo $lockInfo);

    
    function unlock($uri, Locks\LockInfo $lockInfo);

}
