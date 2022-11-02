<?php

namespace Sabre\DAV\FSExt;

use Sabre\DAV;
use Sabre\DAV\FS\Node;


class File extends Node implements DAV\PartialUpdate\IPatchSupport {

    
    function put($data) {

        file_put_contents($this->path, $data);
        clearstatcache(true, $this->path);
        return $this->getETag();

    }

    
    function patch($data, $rangeType, $offset = null) {

        switch ($rangeType) {
            case 1 :
                $f = fopen($this->path, 'a');
                break;
            case 2 :
                $f = fopen($this->path, 'c');
                fseek($f, $offset);
                break;
            case 3 :
                $f = fopen($this->path, 'c');
                fseek($f, $offset, SEEK_END);
                break;
        }
        if (is_string($data)) {
            fwrite($f, $data);
        } else {
            stream_copy_to_stream($data, $f);
        }
        fclose($f);
        clearstatcache(true, $this->path);
        return $this->getETag();

    }

    
    function get() {

        return fopen($this->path, 'r');

    }

    
    function delete() {

        return unlink($this->path);

    }

    
    function getETag() {

        return '"' . sha1(
            fileinode($this->path) .
            filesize($this->path) .
            filemtime($this->path)
        ) . '"';

    }

    
    function getContentType() {

        return null;

    }

    
    function getSize() {

        return filesize($this->path);

    }

}
