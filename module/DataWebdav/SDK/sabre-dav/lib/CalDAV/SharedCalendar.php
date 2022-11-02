<?php

namespace Sabre\CalDAV;

use Sabre\DAV\Sharing\Plugin as SPlugin;


class SharedCalendar extends Calendar implements ISharedCalendar {

    
    function getShareAccess() {

        return isset($this->calendarInfo['share-access']) ? $this->calendarInfo['share-access'] : SPlugin::ACCESS_NOTSHARED;

    }

    
    function getShareResourceUri() {

        return $this->calendarInfo['share-resource-uri'];

    }

    
    function updateInvites(array $sharees) {

        $this->caldavBackend->updateInvites($this->calendarInfo['id'], $sharees);

    }

    
    function getInvites() {

        return $this->caldavBackend->getInvites($this->calendarInfo['id']);

    }

    
    function setPublishStatus($value) {

        $this->caldavBackend->setPublishStatus($this->calendarInfo['id'], $value);

    }

    
    function getACL() {

        $acl = [];

        switch ($this->getShareAccess()) {
            case SPlugin::ACCESS_NOTSHARED :
            case SPlugin::ACCESS_SHAREDOWNER :
                $acl[] = [
                    'privilege' => '{DAV:}share',
                    'principal' => $this->calendarInfo['principaluri'],
                    'protected' => true,
                ];
                $acl[] = [
                    'privilege' => '{DAV:}share',
                    'principal' => $this->calendarInfo['principaluri'] . '/calendar-proxy-write',
                    'protected' => true,
                ];
                            case SPlugin::ACCESS_READWRITE :
                $acl[] = [
                    'privilege' => '{DAV:}write',
                    'principal' => $this->calendarInfo['principaluri'],
                    'protected' => true,
                ];
                $acl[] = [
                    'privilege' => '{DAV:}write',
                    'principal' => $this->calendarInfo['principaluri'] . '/calendar-proxy-write',
                    'protected' => true,
                ];
                            case SPlugin::ACCESS_READ :
                $acl[] = [
                    'privilege' => '{DAV:}write-properties',
                    'principal' => $this->calendarInfo['principaluri'],
                    'protected' => true,
                ];
                $acl[] = [
                    'privilege' => '{DAV:}write-properties',
                    'principal' => $this->calendarInfo['principaluri'] . '/calendar-proxy-write',
                    'protected' => true,
                ];
                $acl[] = [
                    'privilege' => '{DAV:}read',
                    'principal' => $this->calendarInfo['principaluri'],
                    'protected' => true,
                ];
                $acl[] = [
                    'privilege' => '{DAV:}read',
                    'principal' => $this->calendarInfo['principaluri'] . '/calendar-proxy-read',
                    'protected' => true,
                ];
                $acl[] = [
                    'privilege' => '{DAV:}read',
                    'principal' => $this->calendarInfo['principaluri'] . '/calendar-proxy-write',
                    'protected' => true,
                ];
                $acl[] = [
                    'privilege' => '{' . Plugin::NS_CALDAV . '}read-free-busy',
                    'principal' => '{DAV:}authenticated',
                    'protected' => true,
                ];
                break;
        }
        return $acl;

    }


    
    function getChildACL() {

        $acl = [];

        switch ($this->getShareAccess()) {
            case SPlugin::ACCESS_NOTSHARED :
                            case SPlugin::ACCESS_SHAREDOWNER :
                            case SPlugin::ACCESS_READWRITE:
                $acl[] = [
                    'privilege' => '{DAV:}write',
                    'principal' => $this->calendarInfo['principaluri'],
                    'protected' => true,
                ];
                $acl[] = [
                    'privilege' => '{DAV:}write',
                    'principal' => $this->calendarInfo['principaluri'] . '/calendar-proxy-write',
                    'protected' => true,
                ];
                            case SPlugin::ACCESS_READ:
                $acl[] = [
                    'privilege' => '{DAV:}read',
                    'principal' => $this->calendarInfo['principaluri'],
                    'protected' => true,
                ];
                $acl[] = [
                    'privilege' => '{DAV:}read',
                    'principal' => $this->calendarInfo['principaluri'] . '/calendar-proxy-write',
                    'protected' => true,
                ];
                $acl[] = [
                    'privilege' => '{DAV:}read',
                    'principal' => $this->calendarInfo['principaluri'] . '/calendar-proxy-read',
                    'protected' => true,
                ];
                break;
        }

        return $acl;

    }

}
