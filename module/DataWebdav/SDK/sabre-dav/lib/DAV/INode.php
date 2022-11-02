<?php

namespace Sabre\DAV;


interface INode {

    
    function delete();

    
    function getName();

    
    function setName($name);

    
    function getLastModified();

}
