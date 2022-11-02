<?php

namespace Sabre\DAV\Auth\Backend;

use Sabre\DAV;
use Sabre\HTTP;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;


abstract class AbstractBasic implements BackendInterface {

    
    protected $realm = 'sabre/dav';

    
    protected $principalPrefix = 'principals/';

    
    abstract protected function validateUserPass($username, $password);

    
    function setRealm($realm) {

        $this->realm = $realm;

    }

    
    function check(RequestInterface $request, ResponseInterface $response) {

        $auth = new HTTP\Auth\Basic(
            $this->realm,
            $request,
            $response
        );

        $userpass = $auth->getCredentials();
        if (!$userpass) {
            return [false, "No 'Authorization: Basic' header found. Either the client didn't send one, or the server is misconfigured"];
        }
        if (!$this->validateUserPass($userpass[0], $userpass[1])) {
            return [false, "Username or password was incorrect"];
        }
        return [true, $this->principalPrefix . $userpass[0]];

    }

    
    function challenge(RequestInterface $request, ResponseInterface $response) {

        $auth = new HTTP\Auth\Basic(
            $this->realm,
            $request,
            $response
        );
        $auth->requireLogin();

    }

}
