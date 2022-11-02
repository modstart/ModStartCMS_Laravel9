<?php

namespace Sabre\DAV;


interface IProperties extends INode {

    
    function propPatch(PropPatch $propPatch);

    
    function getProperties($properties);

}
