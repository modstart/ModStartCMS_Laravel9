<?php

namespace Sabre\DAV\Sharing;

use Sabre\DAV\INode;


interface ISharedNode extends INode {

    
    function getShareAccess();

    
    function getShareResourceUri();

    
    function updateInvites(array $sharees);

    
    function getInvites();

}
