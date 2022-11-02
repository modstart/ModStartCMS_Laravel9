<?php

namespace Sabre\DAV;


abstract class File extends Node implements IFile {

    
    function put($data) {

        throw new Exception\Forbidden('Permission denied to change data');

    }

    
    function get() {

        throw new Exception\Forbidden('Permission denied to read this file');

    }

    
    function getSize() {

        return 0;

    }

    
    function getETag() {

        return null;

    }

    
    function getContentType() {

        return null;

    }

}
