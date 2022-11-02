<?php

namespace Sabre\DAV\PropertyStorage\Backend;

use Sabre\DAV\PropFind;
use Sabre\DAV\PropPatch;
use Sabre\DAV\Xml\Property\Complex;


class PDO implements BackendInterface {

    
    const VT_STRING = 1;

    
    const VT_XML = 2;

    
    const VT_OBJECT = 3;

    
    protected $pdo;

    
    public $tableName = 'propertystorage';

    
    function __construct(\PDO $pdo) {

        $this->pdo = $pdo;

    }

    
    function propFind($path, PropFind $propFind) {

        if (!$propFind->isAllProps() && count($propFind->get404Properties()) === 0) {
            return;
        }

        $query = 'SELECT name, value, valuetype FROM ' . $this->tableName . ' WHERE path = ?';
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$path]);

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if (gettype($row['value']) === 'resource') {
                $row['value'] = stream_get_contents($row['value']);
            }
            switch ($row['valuetype']) {
                case null :
                case self::VT_STRING :
                    $propFind->set($row['name'], $row['value']);
                    break;
                case self::VT_XML :
                    $propFind->set($row['name'], new Complex($row['value']));
                    break;
                case self::VT_OBJECT :
                    $propFind->set($row['name'], unserialize($row['value']));
                    break;
            }
        }

    }

    
    function propPatch($path, PropPatch $propPatch) {

        $propPatch->handleRemaining(function($properties) use ($path) {


            if ($this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'pgsql') {

                $updateSql = <<<SQL
INSERT INTO {$this->tableName} (path, name, valuetype, value)
VALUES (:path, :name, :valuetype, :value)
ON CONFLICT (path, name)
DO UPDATE SET valuetype = :valuetype, value = :value
SQL;


            } else {
                $updateSql = <<<SQL
REPLACE INTO {$this->tableName} (path, name, valuetype, value)
VALUES (:path, :name, :valuetype, :value)
SQL;

            }

            $updateStmt = $this->pdo->prepare($updateSql);
            $deleteStmt = $this->pdo->prepare("DELETE FROM " . $this->tableName . " WHERE path = ? AND name = ?");

            foreach ($properties as $name => $value) {

                if (!is_null($value)) {
                    if (is_scalar($value)) {
                        $valueType = self::VT_STRING;
                    } elseif ($value instanceof Complex) {
                        $valueType = self::VT_XML;
                        $value = $value->getXml();
                    } else {
                        $valueType = self::VT_OBJECT;
                        $value = serialize($value);
                    }

                    $updateStmt->bindParam('path', $path, \PDO::PARAM_STR);
                    $updateStmt->bindParam('name', $name, \PDO::PARAM_STR);
                    $updateStmt->bindParam('valuetype', $valueType, \PDO::PARAM_INT);
                    $updateStmt->bindParam('value', $value, \PDO::PARAM_LOB);

                    $updateStmt->execute();

                } else {
                    $deleteStmt->execute([$path, $name]);
                }

            }

            return true;

        });

    }

    
    function delete($path) {

        $stmt = $this->pdo->prepare("DELETE FROM " . $this->tableName . "  WHERE path = ? OR path LIKE ? ESCAPE '='");
        $childPath = strtr(
            $path,
            [
                '=' => '==',
                '%' => '=%',
                '_' => '=_'
            ]
        ) . '/%';

        $stmt->execute([$path, $childPath]);

    }

    
    function move($source, $destination) {

                                        $select = $this->pdo->prepare('SELECT id, path FROM ' . $this->tableName . '  WHERE path = ? OR path LIKE ?');
        $select->execute([$source, $source . '/%']);

        $update = $this->pdo->prepare('UPDATE ' . $this->tableName . ' SET path = ? WHERE id = ?');
        while ($row = $select->fetch(\PDO::FETCH_ASSOC)) {

                                    if ($row['path'] !== $source && strpos($row['path'], $source . '/') !== 0) continue;

            $trailingPart = substr($row['path'], strlen($source) + 1);
            $newPath = $destination;
            if ($trailingPart) {
                $newPath .= '/' . $trailingPart;
            }
            $update->execute([$newPath, $row['id']]);

        }

    }

}
