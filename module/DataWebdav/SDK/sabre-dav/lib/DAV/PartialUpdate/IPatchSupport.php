<?php

namespace Sabre\DAV\PartialUpdate;

use Sabre\DAV;


interface IPatchSupport extends DAV\IFile {

    
    function patch($data, $rangeType, $offset = null);

}
