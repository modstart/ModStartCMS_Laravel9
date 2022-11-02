<?php

namespace Sabre\CalDAV\Schedule;

use Sabre\CalDAV\Backend;
use Sabre\DAV\Exception\MethodNotAllowed;


class SchedulingObject extends \Sabre\CalDAV\CalendarObject implements ISchedulingObject {

    
    protected $caldavBackend;

    
    protected $objectData;

    
    function __construct(Backend\SchedulingSupport $caldavBackend, array $objectData) {

        $this->caldavBackend = $caldavBackend;

        if (!isset($objectData['uri'])) {
            throw new \InvalidArgumentException('The objectData argument must contain an \'uri\' property');
        }

        $this->objectData = $objectData;

    }

    
    function get() {

                        if (!isset($this->objectData['calendardata'])) {
            $this->objectData = $this->caldavBackend->getSchedulingObject($this->objectData['principaluri'], $this->objectData['uri']);
        }
        return $this->objectData['calendardata'];

    }

    
    function put($calendarData) {

        throw new MethodNotAllowed('Updating scheduling objects is not supported');

    }

    
    function delete() {

        $this->caldavBackend->deleteSchedulingObject($this->objectData['principaluri'], $this->objectData['uri']);

    }

    
    function getOwner() {

        return $this->objectData['principaluri'];

    }


    
    function getACL() {

                
        if (isset($this->objectData['acl'])) {
            return $this->objectData['acl'];
        }

                return [
            [
                'privilege' => '{DAV:}all',
                'principal' => '{DAV:}owner',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}all',
                'principal' => $this->objectData['principaluri'] . '/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->objectData['principaluri'] . '/calendar-proxy-read',
                'protected' => true,
            ],
        ];

    }

}
