<?php

namespace Sabre\DAV;


interface IFile extends INode {

    
    function put($data);

    
    function get();

    
    function getContentType();

    
    function getETag();

    
    function getSize();

}
