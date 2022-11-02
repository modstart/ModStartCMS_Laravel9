<?php

namespace Sabre\DAVACL\PrincipalBackend;

use Sabre\DAV;
use Sabre\DAV\MkCol;
use Sabre\HTTP\URLUtil;


class PDO extends AbstractBackend implements CreatePrincipalSupport {

    
    public $tableName = 'principals';

    
    public $groupMembersTableName = 'groupmembers';

    
    protected $pdo;

    
    protected $fieldMap = [

        
        '{DAV:}displayname' => [
            'dbField' => 'displayname',
        ],

        
        '{http://sabredav.org/ns}email-address' => [
            'dbField' => 'email',
        ],
    ];

    
    function __construct(\PDO $pdo) {

        $this->pdo = $pdo;

    }

    
    function getPrincipalsByPrefix($prefixPath) {

        $fields = [
            'uri',
        ];

        foreach ($this->fieldMap as $key => $value) {
            $fields[] = $value['dbField'];
        }
        $result = $this->pdo->query('SELECT ' . implode(',', $fields) . '  FROM ' . $this->tableName);

        $principals = [];

        while ($row = $result->fetch(\PDO::FETCH_ASSOC)) {

                        list($rowPrefix) = URLUtil::splitPath($row['uri']);
            if ($rowPrefix !== $prefixPath) continue;

            $principal = [
                'uri' => $row['uri'],
            ];
            foreach ($this->fieldMap as $key => $value) {
                if ($row[$value['dbField']]) {
                    $principal[$key] = $row[$value['dbField']];
                }
            }
            $principals[] = $principal;

        }

        return $principals;

    }

    
    function getPrincipalByPath($path) {

        $fields = [
            'id',
            'uri',
        ];

        foreach ($this->fieldMap as $key => $value) {
            $fields[] = $value['dbField'];
        }
        $stmt = $this->pdo->prepare('SELECT ' . implode(',', $fields) . '  FROM ' . $this->tableName . ' WHERE uri = ?');
        $stmt->execute([$path]);

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) return;

        $principal = [
            'id'  => $row['id'],
            'uri' => $row['uri'],
        ];
        foreach ($this->fieldMap as $key => $value) {
            if ($row[$value['dbField']]) {
                $principal[$key] = $row[$value['dbField']];
            }
        }
        return $principal;

    }

    
    function updatePrincipal($path, DAV\PropPatch $propPatch) {

        $propPatch->handle(array_keys($this->fieldMap), function($properties) use ($path) {

            $query = "UPDATE " . $this->tableName . " SET ";
            $first = true;

            $values = [];

            foreach ($properties as $key => $value) {

                $dbField = $this->fieldMap[$key]['dbField'];

                if (!$first) {
                    $query .= ', ';
                }
                $first = false;
                $query .= $dbField . ' = :' . $dbField;
                $values[$dbField] = $value;

            }

            $query .= " WHERE uri = :uri";
            $values['uri'] = $path;

            $stmt = $this->pdo->prepare($query);
            $stmt->execute($values);

            return true;

        });

    }

    
    function searchPrincipals($prefixPath, array $searchProperties, $test = 'allof') {
        if (count($searchProperties) == 0) return [];    
        $query = 'SELECT uri FROM ' . $this->tableName . ' WHERE ';
        $values = [];
        foreach ($searchProperties as $property => $value) {
            switch ($property) {
                case '{DAV:}displayname' :
                    $column = "displayname";
                    break;
                case '{http://sabredav.org/ns}email-address' :
                    $column = "email";
                    break;
                default :
                                        return [];
            }
            if (count($values) > 0) $query .= (strcmp($test, "anyof") == 0 ? " OR " : " AND ");
            $query .= 'lower(' . $column . ') LIKE lower(?)';
            $values[] = '%' . $value . '%';

        }
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($values);

        $principals = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

                        list($rowPrefix) = URLUtil::splitPath($row['uri']);
            if ($rowPrefix !== $prefixPath) continue;

            $principals[] = $row['uri'];

        }

        return $principals;

    }

    
    function findByUri($uri, $principalPrefix) {
        $value = null;
        $scheme = null;
        list($scheme, $value) = explode(":", $uri, 2);
        if (empty($value)) return null;

        $uri = null;
        switch ($scheme){
            case "mailto":
                $query = 'SELECT uri FROM ' . $this->tableName . ' WHERE lower(email)=lower(?)';
                $stmt = $this->pdo->prepare($query);
                $stmt->execute([$value]);
            
                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                                        list($rowPrefix) = URLUtil::splitPath($row['uri']);
                    if ($rowPrefix !== $principalPrefix) continue;
                    
                    $uri = $row['uri'];
                    break;                 }
                break;
            default:
                                return null;
        }
        return $uri;
    }

    
    function getGroupMemberSet($principal) {

        $principal = $this->getPrincipalByPath($principal);
        if (!$principal) throw new DAV\Exception('Principal not found');

        $stmt = $this->pdo->prepare('SELECT principals.uri as uri FROM ' . $this->groupMembersTableName . ' AS groupmembers LEFT JOIN ' . $this->tableName . ' AS principals ON groupmembers.member_id = principals.id WHERE groupmembers.principal_id = ?');
        $stmt->execute([$principal['id']]);

        $result = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $result[] = $row['uri'];
        }
        return $result;

    }

    
    function getGroupMembership($principal) {

        $principal = $this->getPrincipalByPath($principal);
        if (!$principal) throw new DAV\Exception('Principal not found');

        $stmt = $this->pdo->prepare('SELECT principals.uri as uri FROM ' . $this->groupMembersTableName . ' AS groupmembers LEFT JOIN ' . $this->tableName . ' AS principals ON groupmembers.principal_id = principals.id WHERE groupmembers.member_id = ?');
        $stmt->execute([$principal['id']]);

        $result = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $result[] = $row['uri'];
        }
        return $result;

    }

    
    function setGroupMemberSet($principal, array $members) {

                $stmt = $this->pdo->prepare('SELECT id, uri FROM ' . $this->tableName . ' WHERE uri IN (? ' . str_repeat(', ? ', count($members)) . ');');
        $stmt->execute(array_merge([$principal], $members));

        $memberIds = [];
        $principalId = null;

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if ($row['uri'] == $principal) {
                $principalId = $row['id'];
            } else {
                $memberIds[] = $row['id'];
            }
        }
        if (!$principalId) throw new DAV\Exception('Principal not found');

                $stmt = $this->pdo->prepare('DELETE FROM ' . $this->groupMembersTableName . ' WHERE principal_id = ?;');
        $stmt->execute([$principalId]);

        foreach ($memberIds as $memberId) {

            $stmt = $this->pdo->prepare('INSERT INTO ' . $this->groupMembersTableName . ' (principal_id, member_id) VALUES (?, ?);');
            $stmt->execute([$principalId, $memberId]);

        }

    }

    
    function createPrincipal($path, MkCol $mkCol) {

        $stmt = $this->pdo->prepare('INSERT INTO ' . $this->tableName . ' (uri) VALUES (?)');
        $stmt->execute([$path]);
        $this->updatePrincipal($path, $mkCol);

    }

}
