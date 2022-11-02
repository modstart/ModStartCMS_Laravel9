<?php

namespace Sabre\DAV\FS;

use Sabre\DAV;
use Sabre\HTTP\URLUtil;


abstract class Node implements DAV\INode {

    
    protected $path;

    
    function __construct($path) {

        $this->path = $path;

    }



    
    function getName() {

        list(, $name) = URLUtil::splitPath($this->path);
        return $name;

    }

    
    function setName($name) {

        list($parentPath, ) = URLUtil::splitPath($this->path);
        list(, $newName) = URLUtil::splitPath($name);

        $newPath = $parentPath . '/' . $newName;
        rename($this->path, $newPath);

        $this->path = $newPath;

    }

    
    function getLastModified() {

        return filemtime($this->path);

    }

}
