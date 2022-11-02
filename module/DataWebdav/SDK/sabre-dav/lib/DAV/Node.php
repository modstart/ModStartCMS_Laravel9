<?php

namespace Sabre\DAV;


abstract class Node implements INode {

    
    function getLastModified() {

        return null;

    }

    
    function delete() {

        throw new Exception\Forbidden('Permission denied to delete node');

    }

    
    function setName($name) {

        throw new Exception\Forbidden('Permission denied to rename file');

    }

}
