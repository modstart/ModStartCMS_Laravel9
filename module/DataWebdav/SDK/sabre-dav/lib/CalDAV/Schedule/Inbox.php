<?php

namespace Sabre\CalDAV\Schedule;

use Sabre\CalDAV;
use Sabre\CalDAV\Backend;
use Sabre\DAV;
use Sabre\DAVACL;
use Sabre\VObject;


class Inbox extends DAV\Collection implements IInbox {

    use DAVACL\ACLTrait;

    
    protected $caldavBackend;

    
    protected $principalUri;

    
    function __construct(Backend\SchedulingSupport $caldavBackend, $principalUri) {

        $this->caldavBackend = $caldavBackend;
        $this->principalUri = $principalUri;

    }

    
    function getName() {

        return 'inbox';

    }

    
    function getChildren() {

        $objs = $this->caldavBackend->getSchedulingObjects($this->principalUri);
        $children = [];
        foreach ($objs as $obj) {
                        $obj['principaluri'] = $this->principalUri;
            $children[] = new SchedulingObject($this->caldavBackend, $obj);
        }
        return $children;

    }

    
    function createFile($name, $data = null) {

        $this->caldavBackend->createSchedulingObject($this->principalUri, $name, $data);

    }

    
    function getOwner() {

        return $this->principalUri;

    }

    
    function getACL() {

        return [
            [
                'privilege' => '{DAV:}read',
                'principal' => '{DAV:}authenticated',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}write-properties',
                'principal' => $this->getOwner(),
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}unbind',
                'principal' => $this->getOwner(),
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}unbind',
                'principal' => $this->getOwner() . '/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{' . CalDAV\Plugin::NS_CALDAV . '}schedule-deliver',
                'principal' => '{DAV:}authenticated',
                'protected' => true,
            ],
        ];

    }

    
    function calendarQuery(array $filters) {

        $result = [];
        $validator = new CalDAV\CalendarQueryValidator();

        $objects = $this->caldavBackend->getSchedulingObjects($this->principalUri);
        foreach ($objects as $object) {
            $vObject = VObject\Reader::read($object['calendardata']);
            if ($validator->validate($vObject, $filters)) {
                $result[] = $object['uri'];
            }

                        $vObject->destroy();
        }
        return $result;

    }

}
