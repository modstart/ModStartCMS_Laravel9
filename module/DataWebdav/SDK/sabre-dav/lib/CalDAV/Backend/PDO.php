<?php

namespace Sabre\CalDAV\Backend;

use Sabre\CalDAV;
use Sabre\DAV;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Xml\Element\Sharee;
use Sabre\VObject;


class PDO extends AbstractBackend
    implements
        SyncSupport,
        SubscriptionSupport,
        SchedulingSupport,
        SharingSupport {

    
    const MAX_DATE = '2038-01-01';

    
    protected $pdo;

    
    public $calendarTableName = 'calendars';

    
    public $calendarInstancesTableName = 'calendarinstances';

    
    public $calendarObjectTableName = 'calendarobjects';

    
    public $calendarChangesTableName = 'calendarchanges';

    
    public $schedulingObjectTableName = 'schedulingobjects';

    
    public $calendarSubscriptionsTableName = 'calendarsubscriptions';

    
    public $propertyMap = [
        '{DAV:}displayname'                                   => 'displayname',
        '{urn:ietf:params:xml:ns:caldav}calendar-description' => 'description',
        '{urn:ietf:params:xml:ns:caldav}calendar-timezone'    => 'timezone',
        '{http://apple.com/ns/ical/}calendar-order'           => 'calendarorder',
        '{http://apple.com/ns/ical/}calendar-color'           => 'calendarcolor',
    ];

    
    public $subscriptionPropertyMap = [
        '{DAV:}displayname'                                           => 'displayname',
        '{http://apple.com/ns/ical/}refreshrate'                      => 'refreshrate',
        '{http://apple.com/ns/ical/}calendar-order'                   => 'calendarorder',
        '{http://apple.com/ns/ical/}calendar-color'                   => 'calendarcolor',
        '{http://calendarserver.org/ns/}subscribed-strip-todos'       => 'striptodos',
        '{http://calendarserver.org/ns/}subscribed-strip-alarms'      => 'stripalarms',
        '{http://calendarserver.org/ns/}subscribed-strip-attachments' => 'stripattachments',
    ];

    
    function __construct(\PDO $pdo) {

        $this->pdo = $pdo;

    }

    
    function getCalendarsForUser($principalUri) {

        $fields = array_values($this->propertyMap);
        $fields[] = 'calendarid';
        $fields[] = 'uri';
        $fields[] = 'synctoken';
        $fields[] = 'components';
        $fields[] = 'principaluri';
        $fields[] = 'transparent';
        $fields[] = 'access';

                $fields = implode(', ', $fields);
        $stmt = $this->pdo->prepare(<<<SQL
SELECT {$this->calendarInstancesTableName}.id as id, $fields FROM {$this->calendarInstancesTableName}
    LEFT JOIN {$this->calendarTableName} ON
        {$this->calendarInstancesTableName}.calendarid = {$this->calendarTableName}.id
WHERE principaluri = ? ORDER BY calendarorder ASC
SQL
        );
        $stmt->execute([$principalUri]);

        $calendars = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

            $components = [];
            if ($row['components']) {
                $components = explode(',', $row['components']);
            }

            $calendar = [
                'id'                                                                 => [(int)$row['calendarid'], (int)$row['id']],
                'uri'                                                                => $row['uri'],
                'principaluri'                                                       => $row['principaluri'],
                '{' . CalDAV\Plugin::NS_CALENDARSERVER . '}getctag'                  => 'http://sabre.io/ns/sync/' . ($row['synctoken'] ? $row['synctoken'] : '0'),
                '{http://sabredav.org/ns}sync-token'                                 => $row['synctoken'] ? $row['synctoken'] : '0',
                '{' . CalDAV\Plugin::NS_CALDAV . '}supported-calendar-component-set' => new CalDAV\Xml\Property\SupportedCalendarComponentSet($components),
                '{' . CalDAV\Plugin::NS_CALDAV . '}schedule-calendar-transp'         => new CalDAV\Xml\Property\ScheduleCalendarTransp($row['transparent'] ? 'transparent' : 'opaque'),
                'share-resource-uri'                                                 => '/ns/share/' . $row['calendarid'],
            ];

            $calendar['share-access'] = (int)$row['access'];
                        if ($row['access'] > 1) {
                                                
                                                $calendar['read-only'] = (int)$row['access'] === \Sabre\DAV\Sharing\Plugin::ACCESS_READ;
            }

            foreach ($this->propertyMap as $xmlName => $dbName) {
                $calendar[$xmlName] = $row[$dbName];
            }

            $calendars[] = $calendar;

        }

        return $calendars;

    }

    
    function createCalendar($principalUri, $calendarUri, array $properties) {

        $fieldNames = [
            'principaluri',
            'uri',
            'transparent',
            'calendarid',
        ];
        $values = [
            ':principaluri' => $principalUri,
            ':uri'          => $calendarUri,
            ':transparent'  => 0,
        ];


        $sccs = '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set';
        if (!isset($properties[$sccs])) {
                        $components = 'VEVENT,VTODO';
        } else {
            if (!($properties[$sccs] instanceof CalDAV\Xml\Property\SupportedCalendarComponentSet)) {
                throw new DAV\Exception('The ' . $sccs . ' property must be of type: \Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet');
            }
            $components = implode(',', $properties[$sccs]->getValue());
        }
        $transp = '{' . CalDAV\Plugin::NS_CALDAV . '}schedule-calendar-transp';
        if (isset($properties[$transp])) {
            $values[':transparent'] = $properties[$transp]->getValue() === 'transparent' ? 1 : 0;
        }
        $stmt = $this->pdo->prepare("INSERT INTO " . $this->calendarTableName . " (synctoken, components) VALUES (1, ?)");
        $stmt->execute([$components]);

        $calendarId = $this->pdo->lastInsertId(
            $this->calendarTableName . '_id_seq'
        );

        $values[':calendarid'] = $calendarId;

        foreach ($this->propertyMap as $xmlName => $dbName) {
            if (isset($properties[$xmlName])) {

                $values[':' . $dbName] = $properties[$xmlName];
                $fieldNames[] = $dbName;
            }
        }

        $stmt = $this->pdo->prepare("INSERT INTO " . $this->calendarInstancesTableName . " (" . implode(', ', $fieldNames) . ") VALUES (" . implode(', ', array_keys($values)) . ")");

        $stmt->execute($values);

        return [
            $calendarId,
            $this->pdo->lastInsertId($this->calendarInstancesTableName . '_id_seq')
        ];

    }

    
    function updateCalendar($calendarId, \Sabre\DAV\PropPatch $propPatch) {

        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
        }
        list($calendarId, $instanceId) = $calendarId;

        $supportedProperties = array_keys($this->propertyMap);
        $supportedProperties[] = '{' . CalDAV\Plugin::NS_CALDAV . '}schedule-calendar-transp';

        $propPatch->handle($supportedProperties, function($mutations) use ($calendarId, $instanceId) {
            $newValues = [];
            foreach ($mutations as $propertyName => $propertyValue) {

                switch ($propertyName) {
                    case '{' . CalDAV\Plugin::NS_CALDAV . '}schedule-calendar-transp' :
                        $fieldName = 'transparent';
                        $newValues[$fieldName] = $propertyValue->getValue() === 'transparent';
                        break;
                    default :
                        $fieldName = $this->propertyMap[$propertyName];
                        $newValues[$fieldName] = $propertyValue;
                        break;
                }

            }
            $valuesSql = [];
            foreach ($newValues as $fieldName => $value) {
                $valuesSql[] = $fieldName . ' = ?';
            }

            $stmt = $this->pdo->prepare("UPDATE " . $this->calendarInstancesTableName . " SET " . implode(', ', $valuesSql) . " WHERE id = ?");
            $newValues['id'] = $instanceId;
            $stmt->execute(array_values($newValues));

            $this->addChange($calendarId, "", 2);

            return true;

        });

    }

    
    function deleteCalendar($calendarId) {

        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
        }
        list($calendarId, $instanceId) = $calendarId;

        $stmt = $this->pdo->prepare('SELECT access FROM ' . $this->calendarInstancesTableName . ' where id = ?');
        $stmt->execute([$instanceId]);
        $access = (int)$stmt->fetchColumn();

        if ($access === \Sabre\DAV\Sharing\Plugin::ACCESS_SHAREDOWNER) {

            
            $stmt = $this->pdo->prepare('DELETE FROM ' . $this->calendarObjectTableName . ' WHERE calendarid = ?');
            $stmt->execute([$calendarId]);

            $stmt = $this->pdo->prepare('DELETE FROM ' . $this->calendarChangesTableName . ' WHERE calendarid = ?');
            $stmt->execute([$calendarId]);

            $stmt = $this->pdo->prepare('DELETE FROM ' . $this->calendarInstancesTableName . ' WHERE calendarid = ?');
            $stmt->execute([$calendarId]);

            $stmt = $this->pdo->prepare('DELETE FROM ' . $this->calendarTableName . ' WHERE id = ?');
            $stmt->execute([$calendarId]);

        } else {

            
            $stmt = $this->pdo->prepare('DELETE FROM ' . $this->calendarInstancesTableName . ' WHERE id = ?');
            $stmt->execute([$instanceId]);

        }


    }

    
    function getCalendarObjects($calendarId) {

        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
        }
        list($calendarId, $instanceId) = $calendarId;

        $stmt = $this->pdo->prepare('SELECT id, uri, lastmodified, etag, calendarid, size, componenttype FROM ' . $this->calendarObjectTableName . ' WHERE calendarid = ?');
        $stmt->execute([$calendarId]);

        $result = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $result[] = [
                'id'           => $row['id'],
                'uri'          => $row['uri'],
                'lastmodified' => (int)$row['lastmodified'],
                'etag'         => '"' . $row['etag'] . '"',
                'size'         => (int)$row['size'],
                'component'    => strtolower($row['componenttype']),
            ];
        }

        return $result;

    }

    
    function getCalendarObject($calendarId, $objectUri) {

        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
        }
        list($calendarId, $instanceId) = $calendarId;

        $stmt = $this->pdo->prepare('SELECT id, uri, lastmodified, etag, calendarid, size, calendardata, componenttype FROM ' . $this->calendarObjectTableName . ' WHERE calendarid = ? AND uri = ?');
        $stmt->execute([$calendarId, $objectUri]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) return null;

        return [
            'id'           => $row['id'],
            'uri'          => $row['uri'],
            'lastmodified' => (int)$row['lastmodified'],
            'etag'         => '"' . $row['etag'] . '"',
            'size'         => (int)$row['size'],
            'calendardata' => $row['calendardata'],
            'component'    => strtolower($row['componenttype']),
         ];

    }

    
    function getMultipleCalendarObjects($calendarId, array $uris) {

        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
        }
        list($calendarId, $instanceId) = $calendarId;

        $result = [];
        foreach (array_chunk($uris, 900) as $chunk) {
            $query = 'SELECT id, uri, lastmodified, etag, calendarid, size, calendardata, componenttype FROM ' . $this->calendarObjectTableName . ' WHERE calendarid = ? AND uri IN (';
                        $query .= implode(',', array_fill(0, count($chunk), '?'));
            $query .= ')';

            $stmt = $this->pdo->prepare($query);
            $stmt->execute(array_merge([$calendarId], $chunk));

            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

                $result[] = [
                    'id'           => $row['id'],
                    'uri'          => $row['uri'],
                    'lastmodified' => (int)$row['lastmodified'],
                    'etag'         => '"' . $row['etag'] . '"',
                    'size'         => (int)$row['size'],
                    'calendardata' => $row['calendardata'],
                    'component'    => strtolower($row['componenttype']),
                ];

            }
        }
        return $result;

    }


    
    function createCalendarObject($calendarId, $objectUri, $calendarData) {

        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
        }
        list($calendarId, $instanceId) = $calendarId;

        $extraData = $this->getDenormalizedData($calendarData);

        $stmt = $this->pdo->prepare('INSERT INTO ' . $this->calendarObjectTableName . ' (calendarid, uri, calendardata, lastmodified, etag, size, componenttype, firstoccurence, lastoccurence, uid) VALUES (?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([
            $calendarId,
            $objectUri,
            $calendarData,
            time(),
            $extraData['etag'],
            $extraData['size'],
            $extraData['componentType'],
            $extraData['firstOccurence'],
            $extraData['lastOccurence'],
            $extraData['uid'],
        ]);
        $this->addChange($calendarId, $objectUri, 1);

        return '"' . $extraData['etag'] . '"';

    }

    
    function updateCalendarObject($calendarId, $objectUri, $calendarData) {

        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
        }
        list($calendarId, $instanceId) = $calendarId;

        $extraData = $this->getDenormalizedData($calendarData);

        $stmt = $this->pdo->prepare('UPDATE ' . $this->calendarObjectTableName . ' SET calendardata = ?, lastmodified = ?, etag = ?, size = ?, componenttype = ?, firstoccurence = ?, lastoccurence = ?, uid = ? WHERE calendarid = ? AND uri = ?');
        $stmt->execute([$calendarData, time(), $extraData['etag'], $extraData['size'], $extraData['componentType'], $extraData['firstOccurence'], $extraData['lastOccurence'], $extraData['uid'], $calendarId, $objectUri]);

        $this->addChange($calendarId, $objectUri, 2);

        return '"' . $extraData['etag'] . '"';

    }

    
    protected function getDenormalizedData($calendarData) {

        $vObject = VObject\Reader::read($calendarData);
        $componentType = null;
        $component = null;
        $firstOccurence = null;
        $lastOccurence = null;
        $uid = null;
        foreach ($vObject->getComponents() as $component) {
            if ($component->name !== 'VTIMEZONE') {
                $componentType = $component->name;
                $uid = (string)$component->UID;
                break;
            }
        }
        if (!$componentType) {
            throw new \Sabre\DAV\Exception\BadRequest('Calendar objects must have a VJOURNAL, VEVENT or VTODO component');
        }
        if ($componentType === 'VEVENT') {
            $firstOccurence = $component->DTSTART->getDateTime()->getTimeStamp();
                        if (!isset($component->RRULE)) {
                if (isset($component->DTEND)) {
                    $lastOccurence = $component->DTEND->getDateTime()->getTimeStamp();
                } elseif (isset($component->DURATION)) {
                    $endDate = clone $component->DTSTART->getDateTime();
                    $endDate = $endDate->add(VObject\DateTimeParser::parse($component->DURATION->getValue()));
                    $lastOccurence = $endDate->getTimeStamp();
                } elseif (!$component->DTSTART->hasTime()) {
                    $endDate = clone $component->DTSTART->getDateTime();
                    $endDate = $endDate->modify('+1 day');
                    $lastOccurence = $endDate->getTimeStamp();
                } else {
                    $lastOccurence = $firstOccurence;
                }
            } else {
                $it = new VObject\Recur\EventIterator($vObject, (string)$component->UID);
                $maxDate = new \DateTime(self::MAX_DATE);
                if ($it->isInfinite()) {
                    $lastOccurence = $maxDate->getTimeStamp();
                } else {
                    $end = $it->getDtEnd();
                    while ($it->valid() && $end < $maxDate) {
                        $end = $it->getDtEnd();
                        $it->next();

                    }
                    $lastOccurence = $end->getTimeStamp();
                }

            }

                        if ($firstOccurence < 0) $firstOccurence = 0;
            if ($lastOccurence < 0) $lastOccurence = 0;
        }

                $vObject->destroy();

        return [
            'etag'           => md5($calendarData),
            'size'           => strlen($calendarData),
            'componentType'  => $componentType,
            'firstOccurence' => $firstOccurence,
            'lastOccurence'  => $lastOccurence,
            'uid'            => $uid,
        ];

    }

    
    function deleteCalendarObject($calendarId, $objectUri) {

        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
        }
        list($calendarId, $instanceId) = $calendarId;

        $stmt = $this->pdo->prepare('DELETE FROM ' . $this->calendarObjectTableName . ' WHERE calendarid = ? AND uri = ?');
        $stmt->execute([$calendarId, $objectUri]);

        $this->addChange($calendarId, $objectUri, 3);

    }

    
    function calendarQuery($calendarId, array $filters) {

        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
        }
        list($calendarId, $instanceId) = $calendarId;

        $componentType = null;
        $requirePostFilter = true;
        $timeRange = null;

                if (!$filters['prop-filters'] && !$filters['comp-filters']) {
            $requirePostFilter = false;
        }

                if (count($filters['comp-filters']) > 0 && !$filters['comp-filters'][0]['is-not-defined']) {
            $componentType = $filters['comp-filters'][0]['name'];

                        if (!$filters['prop-filters'] && !$filters['comp-filters'][0]['comp-filters'] && !$filters['comp-filters'][0]['time-range'] && !$filters['comp-filters'][0]['prop-filters']) {
                $requirePostFilter = false;
            }
                        if ($componentType == 'VEVENT' && isset($filters['comp-filters'][0]['time-range'])) {
                $timeRange = $filters['comp-filters'][0]['time-range'];

                                                if (!$filters['prop-filters'] && !$filters['comp-filters'][0]['comp-filters'] && !$filters['comp-filters'][0]['prop-filters'] && (!$timeRange['start'] || !$timeRange['end'])) {
                    $requirePostFilter = false;
                }
            }

        }

        if ($requirePostFilter) {
            $query = "SELECT uri, calendardata FROM " . $this->calendarObjectTableName . " WHERE calendarid = :calendarid";
        } else {
            $query = "SELECT uri FROM " . $this->calendarObjectTableName . " WHERE calendarid = :calendarid";
        }

        $values = [
            'calendarid' => $calendarId,
        ];

        if ($componentType) {
            $query .= " AND componenttype = :componenttype";
            $values['componenttype'] = $componentType;
        }

        if ($timeRange && $timeRange['start']) {
            $query .= " AND lastoccurence > :startdate";
            $values['startdate'] = $timeRange['start']->getTimeStamp();
        }
        if ($timeRange && $timeRange['end']) {
            $query .= " AND firstoccurence < :enddate";
            $values['enddate'] = $timeRange['end']->getTimeStamp();
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($values);

        $result = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if ($requirePostFilter) {
                if (!$this->validateFilterForObject($row, $filters)) {
                    continue;
                }
            }
            $result[] = $row['uri'];

        }

        return $result;

    }

    
    function getCalendarObjectByUID($principalUri, $uid) {

        $query = <<<SQL
SELECT
    calendar_instances.uri AS calendaruri, calendarobjects.uri as objecturi
FROM
    $this->calendarObjectTableName AS calendarobjects
LEFT JOIN
    $this->calendarInstancesTableName AS calendar_instances
    ON calendarobjects.calendarid = calendar_instances.calendarid
WHERE
    calendar_instances.principaluri = ?
    AND
    calendarobjects.uid = ?
SQL;

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$principalUri, $uid]);

        if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return $row['calendaruri'] . '/' . $row['objecturi'];
        }

    }

    
    function getChangesForCalendar($calendarId, $syncToken, $syncLevel, $limit = null) {

        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
        }
        list($calendarId, $instanceId) = $calendarId;

                $stmt = $this->pdo->prepare('SELECT synctoken FROM ' . $this->calendarTableName . ' WHERE id = ?');
        $stmt->execute([$calendarId]);
        $currentToken = $stmt->fetchColumn(0);

        if (is_null($currentToken)) return null;

        $result = [
            'syncToken' => $currentToken,
            'added'     => [],
            'modified'  => [],
            'deleted'   => [],
        ];

        if ($syncToken) {

            $query = "SELECT uri, operation FROM " . $this->calendarChangesTableName . " WHERE synctoken >= ? AND synctoken < ? AND calendarid = ? ORDER BY synctoken";
            if ($limit > 0) $query .= " LIMIT " . (int)$limit;

                        $stmt = $this->pdo->prepare($query);
            $stmt->execute([$syncToken, $currentToken, $calendarId]);

            $changes = [];

                                    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

                $changes[$row['uri']] = $row['operation'];

            }

            foreach ($changes as $uri => $operation) {

                switch ($operation) {
                    case 1 :
                        $result['added'][] = $uri;
                        break;
                    case 2 :
                        $result['modified'][] = $uri;
                        break;
                    case 3 :
                        $result['deleted'][] = $uri;
                        break;
                }

            }
        } else {
                        $query = "SELECT uri FROM " . $this->calendarObjectTableName . " WHERE calendarid = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$calendarId]);

            $result['added'] = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        }
        return $result;

    }

    
    protected function addChange($calendarId, $objectUri, $operation) {

        $stmt = $this->pdo->prepare('INSERT INTO ' . $this->calendarChangesTableName . ' (uri, synctoken, calendarid, operation) SELECT ?, synctoken, ?, ? FROM ' . $this->calendarTableName . ' WHERE id = ?');
        $stmt->execute([
            $objectUri,
            $calendarId,
            $operation,
            $calendarId
        ]);
        $stmt = $this->pdo->prepare('UPDATE ' . $this->calendarTableName . ' SET synctoken = synctoken + 1 WHERE id = ?');
        $stmt->execute([
            $calendarId
        ]);

    }

    
    function getSubscriptionsForUser($principalUri) {

        $fields = array_values($this->subscriptionPropertyMap);
        $fields[] = 'id';
        $fields[] = 'uri';
        $fields[] = 'source';
        $fields[] = 'principaluri';
        $fields[] = 'lastmodified';

                $fields = implode(', ', $fields);
        $stmt = $this->pdo->prepare("SELECT " . $fields . " FROM " . $this->calendarSubscriptionsTableName . " WHERE principaluri = ? ORDER BY calendarorder ASC");
        $stmt->execute([$principalUri]);

        $subscriptions = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

            $subscription = [
                'id'           => $row['id'],
                'uri'          => $row['uri'],
                'principaluri' => $row['principaluri'],
                'source'       => $row['source'],
                'lastmodified' => $row['lastmodified'],

                '{' . CalDAV\Plugin::NS_CALDAV . '}supported-calendar-component-set' => new CalDAV\Xml\Property\SupportedCalendarComponentSet(['VTODO', 'VEVENT']),
            ];

            foreach ($this->subscriptionPropertyMap as $xmlName => $dbName) {
                if (!is_null($row[$dbName])) {
                    $subscription[$xmlName] = $row[$dbName];
                }
            }

            $subscriptions[] = $subscription;

        }

        return $subscriptions;

    }

    
    function createSubscription($principalUri, $uri, array $properties) {

        $fieldNames = [
            'principaluri',
            'uri',
            'source',
            'lastmodified',
        ];

        if (!isset($properties['{http://calendarserver.org/ns/}source'])) {
            throw new Forbidden('The {http://calendarserver.org/ns/}source property is required when creating subscriptions');
        }

        $values = [
            ':principaluri' => $principalUri,
            ':uri'          => $uri,
            ':source'       => $properties['{http://calendarserver.org/ns/}source']->getHref(),
            ':lastmodified' => time(),
        ];

        foreach ($this->subscriptionPropertyMap as $xmlName => $dbName) {
            if (isset($properties[$xmlName])) {

                $values[':' . $dbName] = $properties[$xmlName];
                $fieldNames[] = $dbName;
            }
        }

        $stmt = $this->pdo->prepare("INSERT INTO " . $this->calendarSubscriptionsTableName . " (" . implode(', ', $fieldNames) . ") VALUES (" . implode(', ', array_keys($values)) . ")");
        $stmt->execute($values);

        return $this->pdo->lastInsertId(
            $this->calendarSubscriptionsTableName . '_id_seq'
        );

    }

    
    function updateSubscription($subscriptionId, DAV\PropPatch $propPatch) {

        $supportedProperties = array_keys($this->subscriptionPropertyMap);
        $supportedProperties[] = '{http://calendarserver.org/ns/}source';

        $propPatch->handle($supportedProperties, function($mutations) use ($subscriptionId) {

            $newValues = [];

            foreach ($mutations as $propertyName => $propertyValue) {

                if ($propertyName === '{http://calendarserver.org/ns/}source') {
                    $newValues['source'] = $propertyValue->getHref();
                } else {
                    $fieldName = $this->subscriptionPropertyMap[$propertyName];
                    $newValues[$fieldName] = $propertyValue;
                }

            }

                        $valuesSql = [];
            foreach ($newValues as $fieldName => $value) {
                $valuesSql[] = $fieldName . ' = ?';
            }

            $stmt = $this->pdo->prepare("UPDATE " . $this->calendarSubscriptionsTableName . " SET " . implode(', ', $valuesSql) . ", lastmodified = ? WHERE id = ?");
            $newValues['lastmodified'] = time();
            $newValues['id'] = $subscriptionId;
            $stmt->execute(array_values($newValues));

            return true;

        });

    }

    
    function deleteSubscription($subscriptionId) {

        $stmt = $this->pdo->prepare('DELETE FROM ' . $this->calendarSubscriptionsTableName . ' WHERE id = ?');
        $stmt->execute([$subscriptionId]);

    }

    
    function getSchedulingObject($principalUri, $objectUri) {

        $stmt = $this->pdo->prepare('SELECT uri, calendardata, lastmodified, etag, size FROM ' . $this->schedulingObjectTableName . ' WHERE principaluri = ? AND uri = ?');
        $stmt->execute([$principalUri, $objectUri]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) return null;

        return [
            'uri'          => $row['uri'],
            'calendardata' => $row['calendardata'],
            'lastmodified' => $row['lastmodified'],
            'etag'         => '"' . $row['etag'] . '"',
            'size'         => (int)$row['size'],
         ];

    }

    
    function getSchedulingObjects($principalUri) {

        $stmt = $this->pdo->prepare('SELECT id, calendardata, uri, lastmodified, etag, size FROM ' . $this->schedulingObjectTableName . ' WHERE principaluri = ?');
        $stmt->execute([$principalUri]);

        $result = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $result[] = [
                'calendardata' => $row['calendardata'],
                'uri'          => $row['uri'],
                'lastmodified' => $row['lastmodified'],
                'etag'         => '"' . $row['etag'] . '"',
                'size'         => (int)$row['size'],
            ];
        }

        return $result;

    }

    
    function deleteSchedulingObject($principalUri, $objectUri) {

        $stmt = $this->pdo->prepare('DELETE FROM ' . $this->schedulingObjectTableName . ' WHERE principaluri = ? AND uri = ?');
        $stmt->execute([$principalUri, $objectUri]);

    }

    
    function createSchedulingObject($principalUri, $objectUri, $objectData) {

        $stmt = $this->pdo->prepare('INSERT INTO ' . $this->schedulingObjectTableName . ' (principaluri, calendardata, uri, lastmodified, etag, size) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$principalUri, $objectData, $objectUri, time(), md5($objectData), strlen($objectData)]);

    }

    
    function updateInvites($calendarId, array $sharees) {

        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
        }
        $currentInvites = $this->getInvites($calendarId);
        list($calendarId, $instanceId) = $calendarId;

        $removeStmt = $this->pdo->prepare("DELETE FROM " . $this->calendarInstancesTableName . " WHERE calendarid = ? AND share_href = ? AND access IN (2,3)");
        $updateStmt = $this->pdo->prepare("UPDATE " . $this->calendarInstancesTableName . " SET access = ?, share_displayname = ?, share_invitestatus = ? WHERE calendarid = ? AND share_href = ?");

        $insertStmt = $this->pdo->prepare('
INSERT INTO ' . $this->calendarInstancesTableName . '
    (
        calendarid,
        principaluri,
        access,
        displayname,
        uri,
        description,
        calendarorder,
        calendarcolor,
        timezone,
        transparent,
        share_href,
        share_displayname,
        share_invitestatus
    )
    SELECT
        ?,
        ?,
        ?,
        displayname,
        ?,
        description,
        calendarorder,
        calendarcolor,
        timezone,
        1,
        ?,
        ?,
        ?
    FROM ' . $this->calendarInstancesTableName . ' WHERE id = ?');

        foreach ($sharees as $sharee) {

            if ($sharee->access === \Sabre\DAV\Sharing\Plugin::ACCESS_NOACCESS) {
                                                $removeStmt->execute([$calendarId, $sharee->href]);
                continue;
            }

            if (is_null($sharee->principal)) {
                                                $sharee->inviteStatus = \Sabre\DAV\Sharing\Plugin::INVITE_INVALID;
            } else {
                                                $sharee->inviteStatus = \Sabre\DAV\Sharing\Plugin::INVITE_ACCEPTED;
            }

            foreach ($currentInvites as $oldSharee) {

                if ($oldSharee->href === $sharee->href) {
                                        $sharee->properties = array_merge(
                        $oldSharee->properties,
                        $sharee->properties
                    );
                    $updateStmt->execute([
                        $sharee->access,
                        isset($sharee->properties['{DAV:}displayname']) ? $sharee->properties['{DAV:}displayname'] : null,
                        $sharee->inviteStatus ?: $oldSharee->inviteStatus,
                        $calendarId,
                        $sharee->href
                    ]);
                    continue 2;
                }

            }
                        $insertStmt->execute([
                $calendarId,
                $sharee->principal,
                $sharee->access,
                \Sabre\DAV\UUIDUtil::getUUID(),
                $sharee->href,
                isset($sharee->properties['{DAV:}displayname']) ? $sharee->properties['{DAV:}displayname'] : null,
                $sharee->inviteStatus ?: \Sabre\DAV\Sharing\Plugin::INVITE_NORESPONSE,
                $instanceId
            ]);

        }

    }

    
    function getInvites($calendarId) {

        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to getInvites() is expected to be an array with a calendarId and an instanceId');
        }
        list($calendarId, $instanceId) = $calendarId;

        $query = <<<SQL
SELECT
    principaluri,
    access,
    share_href,
    share_displayname,
    share_invitestatus
FROM {$this->calendarInstancesTableName}
WHERE
    calendarid = ?
SQL;

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$calendarId]);

        $result = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

            $result[] = new Sharee([
                'href'   => isset($row['share_href']) ? $row['share_href'] : \Sabre\HTTP\encodePath($row['principaluri']),
                'access' => (int)$row['access'],
                                'inviteStatus' => (int)$row['share_invitestatus'],
                'properties'   =>
                    !empty($row['share_displayname'])
                    ? ['{DAV:}displayname' => $row['share_displayname']]
                    : [],
                'principal' => $row['principaluri'],
            ]);

        }
        return $result;

    }

    
    function setPublishStatus($calendarId, $value) {

        throw new DAV\Exception\NotImplemented('Not implemented');

    }

}
