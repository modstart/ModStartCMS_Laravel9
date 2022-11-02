<?php

namespace Sabre\DAV\Auth\Backend;

use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;


interface BackendInterface {

    
    function check(RequestInterface $request, ResponseInterface $response);

    
    function challenge(RequestInterface $request, ResponseInterface $response);

}
