<?php

namespace Sabre\DAV;


interface IMultiGet extends ICollection {

    
    function getMultipleChildren(array $paths);

}
