<?php

namespace Sabre\HTTP\Auth;

use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;


abstract class AbstractAuth {

    
    protected $realm;

    
    protected $request;

    
    protected $response;

    
    function __construct($realm = 'SabreTooth', RequestInterface $request, ResponseInterface $response) {

        $this->realm = $realm;
        $this->request = $request;
        $this->response = $response;

    }

    
    abstract function requireLogin();

    
    function getRealm() {

        return $this->realm;

    }

}
