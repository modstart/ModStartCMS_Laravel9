<?php

namespace Sabre\HTTP\Auth;


class Bearer extends AbstractAuth {

    
    function getToken() {

        $auth = $this->request->getHeader('Authorization');

        if (!$auth) {
            return null;
        }

        if (strtolower(substr($auth, 0, 7)) !== 'bearer ') {
            return null;
        }

        return substr($auth, 7);

    }

    
    function requireLogin() {

        $this->response->addHeader('WWW-Authenticate', 'Bearer realm="' . $this->realm . '"');
        $this->response->setStatus(401);

    }

}
