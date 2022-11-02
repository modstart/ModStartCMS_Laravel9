<?php

namespace Sabre\DAV\Auth\Backend;

use Sabre\DAV;


class File extends AbstractDigest {

    
    protected $users = [];

    
    function __construct($filename = null) {

        if (!is_null($filename))
            $this->loadFile($filename);

    }

    
    function loadFile($filename) {

        foreach (file($filename, FILE_IGNORE_NEW_LINES) as $line) {

            if (substr_count($line, ":") !== 2)
                throw new DAV\Exception('Malformed htdigest file. Every line should contain 2 colons');

            list($username, $realm, $A1) = explode(':', $line);

            if (!preg_match('/^[a-zA-Z0-9]{32}$/', $A1))
                throw new DAV\Exception('Malformed htdigest file. Invalid md5 hash');

            $this->users[$realm . ':' . $username] = $A1;

        }

    }

    
    function getDigestHash($realm, $username) {

        return isset($this->users[$realm . ':' . $username]) ? $this->users[$realm . ':' . $username] : false;

    }

}
