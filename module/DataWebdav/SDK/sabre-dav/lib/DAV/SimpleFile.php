<?php

namespace Sabre\DAV;


class SimpleFile extends File {

    
    protected $contents = [];

    
    protected $name;

    
    protected $mimeType;

    
    function __construct($name, $contents, $mimeType = null) {

        $this->name = $name;
        $this->contents = $contents;
        $this->mimeType = $mimeType;

    }

    
    function getName() {

        return $this->name;

    }

    
    function get() {

        return $this->contents;

    }

    
    function getSize() {

        return strlen($this->contents);

    }

    
    function getETag() {

        return '"' . sha1($this->contents) . '"';

    }

    
    function getContentType() {

        return $this->mimeType;

    }

}
