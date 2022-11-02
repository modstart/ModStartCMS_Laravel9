<?php

namespace Sabre\DAV\Auth\Backend;

use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;


class Apache implements BackendInterface {

    
    protected $principalPrefix = 'principals/';

    
    function check(RequestInterface $request, ResponseInterface $response) {

        $remoteUser = $request->getRawServerValue('REMOTE_USER');
        if (is_null($remoteUser)) {
            $remoteUser = $request->getRawServerValue('REDIRECT_REMOTE_USER');
        }
        if (is_null($remoteUser)) {
            return [false, 'No REMOTE_USER property was found in the PHP $_SERVER super-global. This likely means your server is not configured correctly'];
        }

        return [true, $this->principalPrefix . $remoteUser];

    }

    
    function challenge(RequestInterface $request, ResponseInterface $response) {

    }

}
