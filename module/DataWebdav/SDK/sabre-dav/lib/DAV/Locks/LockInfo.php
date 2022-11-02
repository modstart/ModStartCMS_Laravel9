<?php

namespace Sabre\DAV\Locks;


class LockInfo {

    
    const SHARED = 1;

    
    const EXCLUSIVE = 2;

    
    const TIMEOUT_INFINITE = -1;

    
    public $owner;

    
    public $token;

    
    public $timeout;

    
    public $created;

    
    public $scope = self::EXCLUSIVE;

    
    public $depth = 0;

    
    public $uri;

}
