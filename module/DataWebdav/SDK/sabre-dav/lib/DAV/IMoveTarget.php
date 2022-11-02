<?php

namespace Sabre\DAV;


interface IMoveTarget extends ICollection {

    
    function moveInto($targetName, $sourcePath, INode $sourceNode);

}
