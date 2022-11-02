<?php

namespace Sabre\DAV;


interface IExtendedCollection extends ICollection {

    
    function createExtendedCollection($name, MkCol $mkCol);

}
