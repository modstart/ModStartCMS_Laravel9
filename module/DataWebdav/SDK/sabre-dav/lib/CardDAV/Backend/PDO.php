<?php

namespace Sabre\CardDAV\Backend;

use Sabre\CardDAV;
use Sabre\DAV;


class PDO extends AbstractBackend implements SyncSupport {

    
    protected $pdo;

    
    public $addressBooksTableName = 'addressbooks';

    
    public $cardsTableName = 'cards';

    
    public $addressBookChangesTableName = 'addressbookchanges';

    
    function __construct(\PDO $pdo) {

        $this->pdo = $pdo;

    }

    
    function getAddressBooksForUser($principalUri) {

        $stmt = $this->pdo->prepare('SELECT id, uri, displayname, principaluri, description, synctoken FROM ' . $this->addressBooksTableName . ' WHERE principaluri = ?');
        $stmt->execute([$principalUri]);

        $addressBooks = [];

        foreach ($stmt->fetchAll() as $row) {

            $addressBooks[] = [
                'id'                                                          => $row['id'],
                'uri'                                                         => $row['uri'],
                'principaluri'                                                => $row['principaluri'],
                '{DAV:}displayname'                                           => $row['displayname'],
                '{' . CardDAV\Plugin::NS_CARDDAV . '}addressbook-description' => $row['description'],
                '{http://calendarserver.org/ns/}getctag'                      => $row['synctoken'],
                '{http://sabredav.org/ns}sync-token'                          => $row['synctoken'] ? $row['synctoken'] : '0',
            ];

        }

        return $addressBooks;

    }


    
    function updateAddressBook($addressBookId, \Sabre\DAV\PropPatch $propPatch) {

        $supportedProperties = [
            '{DAV:}displayname',
            '{' . CardDAV\Plugin::NS_CARDDAV . '}addressbook-description',
        ];

        $propPatch->handle($supportedProperties, function($mutations) use ($addressBookId) {

            $updates = [];
            foreach ($mutations as $property => $newValue) {

                switch ($property) {
                    case '{DAV:}displayname' :
                        $updates['displayname'] = $newValue;
                        break;
                    case '{' . CardDAV\Plugin::NS_CARDDAV . '}addressbook-description' :
                        $updates['description'] = $newValue;
                        break;
                }
            }
            $query = 'UPDATE ' . $this->addressBooksTableName . ' SET ';
            $first = true;
            foreach ($updates as $key => $value) {
                if ($first) {
                    $first = false;
                } else {
                    $query .= ', ';
                }
                $query .= ' ' . $key . ' = :' . $key . ' ';
            }
            $query .= ' WHERE id = :addressbookid';

            $stmt = $this->pdo->prepare($query);
            $updates['addressbookid'] = $addressBookId;

            $stmt->execute($updates);

            $this->addChange($addressBookId, "", 2);

            return true;

        });

    }

    
    function createAddressBook($principalUri, $url, array $properties) {

        $values = [
            'displayname'  => null,
            'description'  => null,
            'principaluri' => $principalUri,
            'uri'          => $url,
        ];

        foreach ($properties as $property => $newValue) {

            switch ($property) {
                case '{DAV:}displayname' :
                    $values['displayname'] = $newValue;
                    break;
                case '{' . CardDAV\Plugin::NS_CARDDAV . '}addressbook-description' :
                    $values['description'] = $newValue;
                    break;
                default :
                    throw new DAV\Exception\BadRequest('Unknown property: ' . $property);
            }

        }

        $query = 'INSERT INTO ' . $this->addressBooksTableName . ' (uri, displayname, description, principaluri, synctoken) VALUES (:uri, :displayname, :description, :principaluri, 1)';
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($values);
        return $this->pdo->lastInsertId(
            $this->addressBooksTableName . '_id_seq'
        );

    }

    
    function deleteAddressBook($addressBookId) {

        $stmt = $this->pdo->prepare('DELETE FROM ' . $this->cardsTableName . ' WHERE addressbookid = ?');
        $stmt->execute([$addressBookId]);

        $stmt = $this->pdo->prepare('DELETE FROM ' . $this->addressBooksTableName . ' WHERE id = ?');
        $stmt->execute([$addressBookId]);

        $stmt = $this->pdo->prepare('DELETE FROM ' . $this->addressBookChangesTableName . ' WHERE addressbookid = ?');
        $stmt->execute([$addressBookId]);

    }

    
    function getCards($addressbookId) {

        $stmt = $this->pdo->prepare('SELECT id, uri, lastmodified, etag, size FROM ' . $this->cardsTableName . ' WHERE addressbookid = ?');
        $stmt->execute([$addressbookId]);

        $result = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $row['etag'] = '"' . $row['etag'] . '"';
            $row['lastmodified'] = (int)$row['lastmodified'];
            $result[] = $row;
        }
        return $result;

    }

    
    function getCard($addressBookId, $cardUri) {

        $stmt = $this->pdo->prepare('SELECT id, carddata, uri, lastmodified, etag, size FROM ' . $this->cardsTableName . ' WHERE addressbookid = ? AND uri = ? LIMIT 1');
        $stmt->execute([$addressBookId, $cardUri]);

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$result) return false;

        $result['etag'] = '"' . $result['etag'] . '"';
        $result['lastmodified'] = (int)$result['lastmodified'];
        return $result;

    }

    
    function getMultipleCards($addressBookId, array $uris) {

        $query = 'SELECT id, uri, lastmodified, etag, size, carddata FROM ' . $this->cardsTableName . ' WHERE addressbookid = ? AND uri IN (';
                $query .= implode(',', array_fill(0, count($uris), '?'));
        $query .= ')';

        $stmt = $this->pdo->prepare($query);
        $stmt->execute(array_merge([$addressBookId], $uris));
        $result = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $row['etag'] = '"' . $row['etag'] . '"';
            $row['lastmodified'] = (int)$row['lastmodified'];
            $result[] = $row;
        }
        return $result;

    }

    
    function createCard($addressBookId, $cardUri, $cardData) {

        $stmt = $this->pdo->prepare('INSERT INTO ' . $this->cardsTableName . ' (carddata, uri, lastmodified, addressbookid, size, etag) VALUES (?, ?, ?, ?, ?, ?)');

        $etag = md5($cardData);

        $stmt->execute([
            $cardData,
            $cardUri,
            time(),
            $addressBookId,
            strlen($cardData),
            $etag,
        ]);

        $this->addChange($addressBookId, $cardUri, 1);

        return '"' . $etag . '"';

    }

    
    function updateCard($addressBookId, $cardUri, $cardData) {

        $stmt = $this->pdo->prepare('UPDATE ' . $this->cardsTableName . ' SET carddata = ?, lastmodified = ?, size = ?, etag = ? WHERE uri = ? AND addressbookid =?');

        $etag = md5($cardData);
        $stmt->execute([
            $cardData,
            time(),
            strlen($cardData),
            $etag,
            $cardUri,
            $addressBookId
        ]);

        $this->addChange($addressBookId, $cardUri, 2);

        return '"' . $etag . '"';

    }

    
    function deleteCard($addressBookId, $cardUri) {

        $stmt = $this->pdo->prepare('DELETE FROM ' . $this->cardsTableName . ' WHERE addressbookid = ? AND uri = ?');
        $stmt->execute([$addressBookId, $cardUri]);

        $this->addChange($addressBookId, $cardUri, 3);

        return $stmt->rowCount() === 1;

    }

    
    function getChangesForAddressBook($addressBookId, $syncToken, $syncLevel, $limit = null) {

                $stmt = $this->pdo->prepare('SELECT synctoken FROM ' . $this->addressBooksTableName . ' WHERE id = ?');
        $stmt->execute([$addressBookId]);
        $currentToken = $stmt->fetchColumn(0);

        if (is_null($currentToken)) return null;

        $result = [
            'syncToken' => $currentToken,
            'added'     => [],
            'modified'  => [],
            'deleted'   => [],
        ];

        if ($syncToken) {

            $query = "SELECT uri, operation FROM " . $this->addressBookChangesTableName . " WHERE synctoken >= ? AND synctoken < ? AND addressbookid = ? ORDER BY synctoken";
            if ($limit > 0) $query .= " LIMIT " . (int)$limit;

                        $stmt = $this->pdo->prepare($query);
            $stmt->execute([$syncToken, $currentToken, $addressBookId]);

            $changes = [];

                                    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

                $changes[$row['uri']] = $row['operation'];

            }

            foreach ($changes as $uri => $operation) {

                switch ($operation) {
                    case 1:
                        $result['added'][] = $uri;
                        break;
                    case 2:
                        $result['modified'][] = $uri;
                        break;
                    case 3:
                        $result['deleted'][] = $uri;
                        break;
                }

            }
        } else {
                        $query = "SELECT uri FROM " . $this->cardsTableName . " WHERE addressbookid = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$addressBookId]);

            $result['added'] = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        }
        return $result;

    }

    
    protected function addChange($addressBookId, $objectUri, $operation) {

        $stmt = $this->pdo->prepare('INSERT INTO ' . $this->addressBookChangesTableName . ' (uri, synctoken, addressbookid, operation) SELECT ?, synctoken, ?, ? FROM ' . $this->addressBooksTableName . ' WHERE id = ?');
        $stmt->execute([
            $objectUri,
            $addressBookId,
            $operation,
            $addressBookId
        ]);
        $stmt = $this->pdo->prepare('UPDATE ' . $this->addressBooksTableName . ' SET synctoken = synctoken + 1 WHERE id = ?');
        $stmt->execute([
            $addressBookId
        ]);

    }
}
