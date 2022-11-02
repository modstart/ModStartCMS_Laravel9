<?php

namespace Sabre\DAV;


interface ICollection extends INode {

    
    function createFile($name, $data = null);

    
    function createDirectory($name);

    
    function getChild($name);

    
    function getChildren();

    
    function childExists($name);

}
