<?php

namespace Sabre\DAV\Auth\Backend;


class PDO extends AbstractDigest {

    
    protected $pdo;

    
    public $tableName = 'users';


    
    function __construct(\PDO $pdo) {

        $this->pdo = $pdo;

    }

    
    function getDigestHash($realm, $username) {

        $stmt = $this->pdo->prepare('SELECT digesta1 FROM ' . $this->tableName . ' WHERE username = ?');
        $stmt->execute([$username]);
        return $stmt->fetchColumn() ?: null;

    }

}
