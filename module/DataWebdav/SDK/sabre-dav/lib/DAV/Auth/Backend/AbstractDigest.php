<?php

namespace Sabre\DAV\Auth\Backend;

use Sabre\DAV;
use Sabre\HTTP;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;


abstract class AbstractDigest implements BackendInterface {

    
    protected $realm = 'SabreDAV';

    
    protected $principalPrefix = 'principals/';

    
    function setRealm($realm) {

        $this->realm = $realm;

    }

    
    abstract function getDigestHash($realm, $username);

    
    function check(RequestInterface $request, ResponseInterface $response) {

        $digest = new HTTP\Auth\Digest(
            $this->realm,
            $request,
            $response
        );
        $digest->init();

        $username = $digest->getUsername();

                if (!$username) {
            return [false, "No 'Authorization: Digest' header found. Either the client didn't send one, or the server is misconfigured"];
        }

        $hash = $this->getDigestHash($this->realm, $username);
                if ($hash === false || is_null($hash)) {
            return [false, "Username or password was incorrect"];
        }
        if (!is_string($hash)) {
            throw new DAV\Exception('The returned value from getDigestHash must be a string or null');
        }

                if (!$digest->validateA1($hash)) {
            return [false, "Username or password was incorrect"];
        }

        return [true, $this->principalPrefix . $username];

    }

    
    function challenge(RequestInterface $request, ResponseInterface $response) {

        $auth = new HTTP\Auth\Digest(
            $this->realm,
            $request,
            $response
        );
        $auth->init();

        $oldStatus = $response->getStatus() ?: 200;
        $auth->requireLogin();

                        $response->setStatus($oldStatus);

    }

}
