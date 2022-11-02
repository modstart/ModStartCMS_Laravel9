<?php

namespace Sabre\Xml;


trait ContextStackTrait {

    
    public $elementMap = [];

    
    public $contextUri;

    
    public $namespaceMap = [];

    
    public $classMap = [];

    
    protected $contextStack = [];

    
    function pushContext() {

        $this->contextStack[] = [
            $this->elementMap,
            $this->contextUri,
            $this->namespaceMap,
            $this->classMap
        ];

    }

    
    function popContext() {

        list(
            $this->elementMap,
            $this->contextUri,
            $this->namespaceMap,
            $this->classMap
        ) = array_pop($this->contextStack);

    }

}
