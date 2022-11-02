<?php

namespace Sabre\CalDAV;

use Sabre\DAV;
use Sabre\DAV\PropPatch;
use Sabre\DAVACL;


class Calendar implements ICalendar, DAV\IProperties, DAV\Sync\ISyncCollection, DAV\IMultiGet {

    use DAVACL\ACLTrait;

    
    protected $calendarInfo;

    
    protected $caldavBackend;

    
    function __construct(Backend\BackendInterface $caldavBackend, $calendarInfo) {

        $this->caldavBackend = $caldavBackend;
        $this->calendarInfo = $calendarInfo;

    }

    
    function getName() {

        return $this->calendarInfo['uri'];

    }

    
    function propPatch(PropPatch $propPatch) {

        return $this->caldavBackend->updateCalendar($this->calendarInfo['id'], $propPatch);

    }

    
    function getProperties($requestedProperties) {

        $response = [];

        foreach ($this->calendarInfo as $propName => $propValue) {

            if (!is_null($propValue) && $propName[0] === '{')
                $response[$propName] = $this->calendarInfo[$propName];

        }
        return $response;

    }

    
    function getChild($name) {

        $obj = $this->caldavBackend->getCalendarObject($this->calendarInfo['id'], $name);

        if (!$obj) throw new DAV\Exception\NotFound('Calendar object not found');

        $obj['acl'] = $this->getChildACL();

        return new CalendarObject($this->caldavBackend, $this->calendarInfo, $obj);

    }

    
    function getChildren() {

        $objs = $this->caldavBackend->getCalendarObjects($this->calendarInfo['id']);
        $children = [];
        foreach ($objs as $obj) {
            $obj['acl'] = $this->getChildACL();
            $children[] = new CalendarObject($this->caldavBackend, $this->calendarInfo, $obj);
        }
        return $children;

    }

    
    function getMultipleChildren(array $paths) {

        $objs = $this->caldavBackend->getMultipleCalendarObjects($this->calendarInfo['id'], $paths);
        $children = [];
        foreach ($objs as $obj) {
            $obj['acl'] = $this->getChildACL();
            $children[] = new CalendarObject($this->caldavBackend, $this->calendarInfo, $obj);
        }
        return $children;

    }

    
    function childExists($name) {

        $obj = $this->caldavBackend->getCalendarObject($this->calendarInfo['id'], $name);
        if (!$obj)
            return false;
        else
            return true;

    }

    
    function createDirectory($name) {

        throw new DAV\Exception\MethodNotAllowed('Creating collections in calendar objects is not allowed');

    }

    
    function createFile($name, $calendarData = null) {

        if (is_resource($calendarData)) {
            $calendarData = stream_get_contents($calendarData);
        }
        return $this->caldavBackend->createCalendarObject($this->calendarInfo['id'], $name, $calendarData);

    }

    
    function delete() {

        $this->caldavBackend->deleteCalendar($this->calendarInfo['id']);

    }

    
    function setName($newName) {

        throw new DAV\Exception\MethodNotAllowed('Renaming calendars is not yet supported');

    }

    
    function getLastModified() {

        return null;

    }

    
    function getOwner() {

        return $this->calendarInfo['principaluri'];

    }

    
    function getACL() {

        $acl = [
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->getOwner(),
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->getOwner() . '/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->getOwner() . '/calendar-proxy-read',
                'protected' => true,
            ],
            [
                'privilege' => '{' . Plugin::NS_CALDAV . '}read-free-busy',
                'principal' => '{DAV:}authenticated',
                'protected' => true,
            ],

        ];
        if (empty($this->calendarInfo['{http://sabredav.org/ns}read-only'])) {
            $acl[] = [
                'privilege' => '{DAV:}write',
                'principal' => $this->getOwner(),
                'protected' => true,
            ];
            $acl[] = [
                'privilege' => '{DAV:}write',
                'principal' => $this->getOwner() . '/calendar-proxy-write',
                'protected' => true,
            ];
        }

        return $acl;

    }

    
    function getChildACL() {

        $acl = [
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->getOwner(),
                'protected' => true,
            ],

            [
                'privilege' => '{DAV:}read',
                'principal' => $this->getOwner() . '/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->getOwner() . '/calendar-proxy-read',
                'protected' => true,
            ],

        ];
        if (empty($this->calendarInfo['{http://sabredav.org/ns}read-only'])) {
            $acl[] = [
                'privilege' => '{DAV:}write',
                'principal' => $this->getOwner(),
                'protected' => true,
            ];
            $acl[] = [
                'privilege' => '{DAV:}write',
                'principal' => $this->getOwner() . '/calendar-proxy-write',
                'protected' => true,
            ];

        }
        return $acl;

    }


    
    function calendarQuery(array $filters) {

        return $this->caldavBackend->calendarQuery($this->calendarInfo['id'], $filters);

    }

    
    function getSyncToken() {

        if (
            $this->caldavBackend instanceof Backend\SyncSupport &&
            isset($this->calendarInfo['{DAV:}sync-token'])
        ) {
            return $this->calendarInfo['{DAV:}sync-token'];
        }
        if (
            $this->caldavBackend instanceof Backend\SyncSupport &&
            isset($this->calendarInfo['{http://sabredav.org/ns}sync-token'])
        ) {
            return $this->calendarInfo['{http://sabredav.org/ns}sync-token'];
        }

    }

    
    function getChanges($syncToken, $syncLevel, $limit = null) {

        if (!$this->caldavBackend instanceof Backend\SyncSupport) {
            return null;
        }

        return $this->caldavBackend->getChangesForCalendar(
            $this->calendarInfo['id'],
            $syncToken,
            $syncLevel,
            $limit
        );

    }

}
