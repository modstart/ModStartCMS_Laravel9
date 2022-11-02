<?php

namespace Sabre\DAV;


class SimpleCollection extends Collection {

    
    protected $children = [];

    
    protected $name;

    
    function __construct($name, array $children = []) {

        $this->name = $name;
        foreach ($children as $child) {

            if (!($child instanceof INode)) throw new Exception('Only instances of Sabre\DAV\INode are allowed to be passed in the children argument');
            $this->addChild($child);

        }

    }

    
    function addChild(INode $child) {

        $this->children[$child->getName()] = $child;

    }

    
    function getName() {

        return $this->name;

    }

    
    function getChild($name) {

        if (isset($this->children[$name])) return $this->children[$name];
        throw new Exception\NotFound('File not found: ' . $name . ' in \'' . $this->getName() . '\'');

    }

    
    function getChildren() {

        return array_values($this->children);

    }


}
