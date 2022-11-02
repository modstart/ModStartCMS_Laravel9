<?php

namespace Sabre\DAV;


class MkCol extends PropPatch {

    
    protected $resourceType;

    
    function __construct(array $resourceType, array $mutations) {

        $this->resourceType = $resourceType;
        parent::__construct($mutations);

    }

    
    function getResourceType() {

        return $this->resourceType;

    }

    
    function hasResourceType($resourceType) {

        return count(array_diff((array)$resourceType, $this->resourceType)) === 0;

    }

}
