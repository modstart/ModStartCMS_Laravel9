<?php

namespace Sabre\DAV\Locks\Backend;

use Sabre\DAV\Locks\LockInfo;


class PDO extends AbstractBackend {

    
    public $tableName = 'locks';

    
    protected $pdo;

    
    function __construct(\PDO $pdo) {

        $this->pdo = $pdo;

    }

    
    function getLocks($uri, $returnChildLocks) {

                                $query = 'SELECT owner, token, timeout, created, scope, depth, uri FROM ' . $this->tableName . ' WHERE (created > (? - timeout)) AND ((uri = ?)';
        $params = [time(),$uri];

                $uriParts = explode('/', $uri);

                array_pop($uriParts);

        $currentPath = '';

        foreach ($uriParts as $part) {

            if ($currentPath) $currentPath .= '/';
            $currentPath .= $part;

            $query .= ' OR (depth!=0 AND uri = ?)';
            $params[] = $currentPath;

        }

        if ($returnChildLocks) {

            $query .= ' OR (uri LIKE ?)';
            $params[] = $uri . '/%';

        }
        $query .= ')';

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetchAll();

        $lockList = [];
        foreach ($result as $row) {

            $lockInfo = new LockInfo();
            $lockInfo->owner = $row['owner'];
            $lockInfo->token = $row['token'];
            $lockInfo->timeout = $row['timeout'];
            $lockInfo->created = $row['created'];
            $lockInfo->scope = $row['scope'];
            $lockInfo->depth = $row['depth'];
            $lockInfo->uri = $row['uri'];
            $lockList[] = $lockInfo;

        }

        return $lockList;

    }

    
    function lock($uri, LockInfo $lockInfo) {

                $lockInfo->timeout = 30 * 60;
        $lockInfo->created = time();
        $lockInfo->uri = $uri;

        $locks = $this->getLocks($uri, false);
        $exists = false;
        foreach ($locks as $lock) {
            if ($lock->token == $lockInfo->token) $exists = true;
        }

        if ($exists) {
            $stmt = $this->pdo->prepare('UPDATE ' . $this->tableName . ' SET owner = ?, timeout = ?, scope = ?, depth = ?, uri = ?, created = ? WHERE token = ?');
            $stmt->execute([
                $lockInfo->owner,
                $lockInfo->timeout,
                $lockInfo->scope,
                $lockInfo->depth,
                $uri,
                $lockInfo->created,
                $lockInfo->token
            ]);
        } else {
            $stmt = $this->pdo->prepare('INSERT INTO ' . $this->tableName . ' (owner,timeout,scope,depth,uri,created,token) VALUES (?,?,?,?,?,?,?)');
            $stmt->execute([
                $lockInfo->owner,
                $lockInfo->timeout,
                $lockInfo->scope,
                $lockInfo->depth,
                $uri,
                $lockInfo->created,
                $lockInfo->token
            ]);
        }

        return true;

    }



    
    function unlock($uri, LockInfo $lockInfo) {

        $stmt = $this->pdo->prepare('DELETE FROM ' . $this->tableName . ' WHERE uri = ? AND token = ?');
        $stmt->execute([$uri, $lockInfo->token]);

        return $stmt->rowCount() === 1;

    }

}
