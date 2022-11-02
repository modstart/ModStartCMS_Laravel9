<?php

namespace Sabre\DAV;


abstract class Collection extends Node implements ICollection {

    
    function getChild($name) {

        foreach ($this->getChildren() as $child) {

            if ($child->getName() === $name) return $child;

        }
        throw new Exception\NotFound('File not found: ' . $name);

    }

    
    function childExists($name) {

        try {

            $this->getChild($name);
            return true;

        } catch (Exception\NotFound $e) {

            return false;

        }

    }

    
    function createFile($name, $data = null) {

        throw new Exception\Forbidden('Permission denied to create file (filename ' . $name . ')');

    }

    
    function createDirectory($name) {

        throw new Exception\Forbidden('Permission denied to create directory');

    }


}
