<?php

namespace Sabre\CalDAV\Backend;

use Sabre\CalDAV;
use Sabre\DAV;


class SimplePDO extends AbstractBackend {

    
    protected $pdo;

    
    function __construct(\PDO $pdo) {

        $this->pdo = $pdo;

    }

    
    function getCalendarsForUser($principalUri) {

                $stmt = $this->pdo->prepare("SELECT id, uri FROM simple_calendars WHERE principaluri = ? ORDER BY id ASC");
        $stmt->execute([$principalUri]);

        $calendars = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

            $calendars[] = [
                'id'           => $row['id'],
                'uri'          => $row['uri'],
                'principaluri' => $principalUri,
            ];

        }

        return $calendars;

    }

    
    function createCalendar($principalUri, $calendarUri, array $properties) {

        $stmt = $this->pdo->prepare("INSERT INTO simple_calendars (principaluri, uri) VALUES (?, ?)");
        $stmt->execute([$principalUri, $calendarUri]);

        return $this->pdo->lastInsertId();

    }

    
    function deleteCalendar($calendarId) {

        $stmt = $this->pdo->prepare('DELETE FROM simple_calendarobjects WHERE calendarid = ?');
        $stmt->execute([$calendarId]);

        $stmt = $this->pdo->prepare('DELETE FROM simple_calendars WHERE id = ?');
        $stmt->execute([$calendarId]);

    }

    
    function getCalendarObjects($calendarId) {

        $stmt = $this->pdo->prepare('SELECT id, uri, calendardata FROM simple_calendarobjects WHERE calendarid = ?');
        $stmt->execute([$calendarId]);

        $result = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $result[] = [
                'id'           => $row['id'],
                'uri'          => $row['uri'],
                'etag'         => '"' . md5($row['calendardata']) . '"',
                'calendarid'   => $calendarId,
                'size'         => strlen($row['calendardata']),
                'calendardata' => $row['calendardata'],
            ];
        }

        return $result;

    }

    
    function getCalendarObject($calendarId, $objectUri) {

        $stmt = $this->pdo->prepare('SELECT id, uri, calendardata FROM simple_calendarobjects WHERE calendarid = ? AND uri = ?');
        $stmt->execute([$calendarId, $objectUri]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) return null;

        return [
            'id'           => $row['id'],
            'uri'          => $row['uri'],
            'etag'         => '"' . md5($row['calendardata']) . '"',
            'calendarid'   => $calendarId,
            'size'         => strlen($row['calendardata']),
            'calendardata' => $row['calendardata'],
         ];

    }

    
    function createCalendarObject($calendarId, $objectUri, $calendarData) {

        $stmt = $this->pdo->prepare('INSERT INTO simple_calendarobjects (calendarid, uri, calendardata) VALUES (?,?,?)');
        $stmt->execute([
            $calendarId,
            $objectUri,
            $calendarData
        ]);

        return '"' . md5($calendarData) . '"';

    }

    
    function updateCalendarObject($calendarId, $objectUri, $calendarData) {

        $stmt = $this->pdo->prepare('UPDATE simple_calendarobjects SET calendardata = ? WHERE calendarid = ? AND uri = ?');
        $stmt->execute([$calendarData, $calendarId, $objectUri]);

        return '"' . md5($calendarData) . '"';

    }

    
    function deleteCalendarObject($calendarId, $objectUri) {

        $stmt = $this->pdo->prepare('DELETE FROM simple_calendarobjects WHERE calendarid = ? AND uri = ?');
        $stmt->execute([$calendarId, $objectUri]);

    }

}
