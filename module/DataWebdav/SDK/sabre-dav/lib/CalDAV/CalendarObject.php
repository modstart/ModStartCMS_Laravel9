<?php

namespace Sabre\CalDAV;


class CalendarObject extends \Sabre\DAV\File implements ICalendarObject, \Sabre\DAVACL\IACL {

    use \Sabre\DAVACL\ACLTrait;

    
    protected $caldavBackend;

    
    protected $objectData;

    
    protected $calendarInfo;

    
    function __construct(Backend\BackendInterface $caldavBackend, array $calendarInfo, array $objectData) {

        $this->caldavBackend = $caldavBackend;

        if (!isset($objectData['uri'])) {
            throw new \InvalidArgumentException('The objectData argument must contain an \'uri\' property');
        }

        $this->calendarInfo = $calendarInfo;
        $this->objectData = $objectData;

    }

    
    function getName() {

        return $this->objectData['uri'];

    }

    
    function get() {

                        if (!isset($this->objectData['calendardata'])) {
            $this->objectData = $this->caldavBackend->getCalendarObject($this->calendarInfo['id'], $this->objectData['uri']);
        }
        return $this->objectData['calendardata'];

    }

    
    function put($calendarData) {

        if (is_resource($calendarData)) {
            $calendarData = stream_get_contents($calendarData);
        }
        $etag = $this->caldavBackend->updateCalendarObject($this->calendarInfo['id'], $this->objectData['uri'], $calendarData);
        $this->objectData['calendardata'] = $calendarData;
        $this->objectData['etag'] = $etag;

        return $etag;

    }

    
    function delete() {

        $this->caldavBackend->deleteCalendarObject($this->calendarInfo['id'], $this->objectData['uri']);

    }

    
    function getContentType() {

        $mime = 'text/calendar; charset=utf-8';
        if (isset($this->objectData['component']) && $this->objectData['component']) {
            $mime .= '; component=' . $this->objectData['component'];
        }
        return $mime;

    }

    
    function getETag() {

        if (isset($this->objectData['etag'])) {
            return $this->objectData['etag'];
        } else {
            return '"' . md5($this->get()) . '"';
        }

    }

    
    function getLastModified() {

        return $this->objectData['lastmodified'];

    }

    
    function getSize() {

        if (array_key_exists('size', $this->objectData)) {
            return $this->objectData['size'];
        } else {
            return strlen($this->get());
        }

    }

    
    function getOwner() {

        return $this->calendarInfo['principaluri'];

    }

    
    function getACL() {

                if (isset($this->objectData['acl'])) {
            return $this->objectData['acl'];
        }

                return [
            [
                'privilege' => '{DAV:}all',
                'principal' => $this->calendarInfo['principaluri'],
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}all',
                'principal' => $this->calendarInfo['principaluri'] . '/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->calendarInfo['principaluri'] . '/calendar-proxy-read',
                'protected' => true,
            ],

        ];

    }

}
