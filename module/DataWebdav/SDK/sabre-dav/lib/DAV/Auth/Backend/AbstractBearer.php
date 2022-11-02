<?php

namespace Sabre\DAV\Auth\Backend;

use Sabre\DAV;
use Sabre\HTTP;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;


abstract class AbstractBearer implements BackendInterface {

    
    protected $realm = 'sabre/dav';

    
    abstract protected function validateBearerToken($bearerToken);

    
    function setRealm($realm) {

        $this->realm = $realm;

    }

    
    function check(RequestInterface $request, ResponseInterface $response) {

        $auth = new HTTP\Auth\Bearer(
            $this->realm,
            $request,
            $response
        );

        $bearerToken = $auth->getToken($request);
        if (!$bearerToken) {
            return [false, "No 'Authorization: Bearer' header found. Either the client didn't send one, or the server is mis-configured"];
        }
        $principalUrl = $this->validateBearerToken($bearerToken);
        if (!$principalUrl) {
            return [false, "Bearer token was incorrect"];
        }
        return [true, $principalUrl];

    }

    
    function challenge(RequestInterface $request, ResponseInterface $response) {

        $auth = new HTTP\Auth\Bearer(
            $this->realm,
            $request,
            $response
        );
        $auth->requireLogin();

    }

}
