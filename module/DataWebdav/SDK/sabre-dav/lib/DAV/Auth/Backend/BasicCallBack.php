<?php

namespace Sabre\DAV\Auth\Backend;


class BasicCallBack extends AbstractBasic {

    
    protected $callBack;

    
    function __construct(callable $callBack) {

        $this->callBack = $callBack;

    }

    
    protected function validateUserPass($username, $password) {

        $cb = $this->callBack;
        return $cb($username, $password);

    }

}
