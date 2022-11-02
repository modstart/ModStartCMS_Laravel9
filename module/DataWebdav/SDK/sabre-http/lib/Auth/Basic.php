<?php

namespace Sabre\HTTP\Auth;


class Basic extends AbstractAuth {

    
    function getCredentials() {

        $auth = $this->request->getHeader('Authorization');

        if (!$auth) {
            return null;
        }

        if (strtolower(substr($auth, 0, 6)) !== 'basic ') {
            return null;
        }

        $credentials = explode(':', base64_decode(substr($auth, 6)), 2);

        if (2 !== count($credentials)) {
            return null;
        }

        return $credentials;

    }

    
    function requireLogin() {

        $this->response->addHeader('WWW-Authenticate', 'Basic realm="' . $this->realm . '", charset="UTF-8"');
        $this->response->setStatus(401);

    }

}
