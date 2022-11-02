<?php

namespace Sabre\DAV;


class Exception extends \Exception {

    
    function getHTTPCode() {

        return 500;

    }

    
    function serialize(Server $server, \DOMElement $errorNode) {


    }

    
    function getHTTPHeaders(Server $server) {

        return [];

    }

}
