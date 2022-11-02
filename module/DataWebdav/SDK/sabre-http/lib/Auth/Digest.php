<?php

namespace Sabre\HTTP\Auth;

use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;


class Digest extends AbstractAuth {

    
    const QOP_AUTH = 1;
    const QOP_AUTHINT = 2;

    protected $nonce;
    protected $opaque;
    protected $digestParts;
    protected $A1;
    protected $qop = self::QOP_AUTH;

    
    function __construct($realm = 'SabreTooth', RequestInterface $request, ResponseInterface $response) {

        $this->nonce = uniqid();
        $this->opaque = md5($realm);
        parent::__construct($realm, $request, $response);

    }

    
    function init() {

        $digest = $this->getDigest();
        $this->digestParts = $this->parseDigest($digest);

    }

    
    function setQOP($qop) {

        $this->qop = $qop;

    }

    
    function validateA1($A1) {

        $this->A1 = $A1;
        return $this->validate();

    }

    
    function validatePassword($password) {

        $this->A1 = md5($this->digestParts['username'] . ':' . $this->realm . ':' . $password);
        return $this->validate();

    }

    
    function getUsername() {

        return $this->digestParts['username'];

    }

    
    protected function validate() {

        $A2 = $this->request->getMethod() . ':' . $this->digestParts['uri'];

        if ($this->digestParts['qop'] == 'auth-int') {
                        if (!($this->qop & self::QOP_AUTHINT)) return false;
                        $body = $this->request->getBody($asString = true);
            $this->request->setBody($body);
            $A2 .= ':' . md5($body);
        } else {

                        if (!($this->qop & self::QOP_AUTH)) return false;
        }

        $A2 = md5($A2);

        $validResponse = md5("{$this->A1}:{$this->digestParts['nonce']}:{$this->digestParts['nc']}:{$this->digestParts['cnonce']}:{$this->digestParts['qop']}:{$A2}");

        return $this->digestParts['response'] == $validResponse;


    }

    
    function requireLogin() {

        $qop = '';
        switch ($this->qop) {
            case self::QOP_AUTH    :
                $qop = 'auth';
                break;
            case self::QOP_AUTHINT :
                $qop = 'auth-int';
                break;
            case self::QOP_AUTH | self::QOP_AUTHINT :
                $qop = 'auth,auth-int';
                break;
        }

        $this->response->addHeader('WWW-Authenticate', 'Digest realm="' . $this->realm . '",qop="' . $qop . '",nonce="' . $this->nonce . '",opaque="' . $this->opaque . '"');
        $this->response->setStatus(401);

    }


    
    function getDigest() {

        return $this->request->getHeader('Authorization');

    }


    
    protected function parseDigest($digest) {

                $needed_parts = ['nonce' => 1, 'nc' => 1, 'cnonce' => 1, 'qop' => 1, 'username' => 1, 'uri' => 1, 'response' => 1];
        $data = [];

        preg_match_all('@(\w+)=(?:(?:")([^"]+)"|([^\s,$]+))@', $digest, $matches, PREG_SET_ORDER);

        foreach ($matches as $m) {
            $data[$m[1]] = $m[2] ? $m[2] : $m[3];
            unset($needed_parts[$m[1]]);
        }

        return $needed_parts ? false : $data;

    }

}
