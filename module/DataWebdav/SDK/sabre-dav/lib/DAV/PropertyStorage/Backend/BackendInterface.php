<?php

namespace Sabre\DAV\PropertyStorage\Backend;

use Sabre\DAV\PropFind;
use Sabre\DAV\PropPatch;


interface BackendInterface {

    
    function propFind($path, PropFind $propFind);

    
    function propPatch($path, PropPatch $propPatch);

    
    function delete($path);

    
    function move($source, $destination);

}
