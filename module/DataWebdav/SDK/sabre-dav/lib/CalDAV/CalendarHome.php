<?php

namespace Sabre\CalDAV;

use Sabre\DAV;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\MkCol;
use Sabre\DAVACL;
use Sabre\HTTP\URLUtil;


class CalendarHome implements DAV\IExtendedCollection, DAVACL\IACL {

    use DAVACL\ACLTrait;

    
    protected $caldavBackend;

    
    protected $principalInfo;

    
    function __construct(Backend\BackendInterface $caldavBackend, $principalInfo) {

        $this->caldavBackend = $caldavBackend;
        $this->principalInfo = $principalInfo;

    }

    
    function getName() {

        list(, $name) = URLUtil::splitPath($this->principalInfo['uri']);
        return $name;

    }

    
    function setName($name) {

        throw new DAV\Exception\Forbidden();

    }

    
    function delete() {

        throw new DAV\Exception\Forbidden();

    }

    
    function getLastModified() {

        return null;

    }

    
    function createFile($filename, $data = null) {

        throw new DAV\Exception\MethodNotAllowed('Creating new files in this collection is not supported');

    }

    
    function createDirectory($filename) {

        throw new DAV\Exception\MethodNotAllowed('Creating new collections in this collection is not supported');

    }

    
    function getChild($name) {

                if ($name === 'inbox' && $this->caldavBackend instanceof Backend\SchedulingSupport) {
            return new Schedule\Inbox($this->caldavBackend, $this->principalInfo['uri']);
        }
        if ($name === 'outbox' && $this->caldavBackend instanceof Backend\SchedulingSupport) {
            return new Schedule\Outbox($this->principalInfo['uri']);
        }
        if ($name === 'notifications' && $this->caldavBackend instanceof Backend\NotificationSupport) {
            return new Notifications\Collection($this->caldavBackend, $this->principalInfo['uri']);
        }

                foreach ($this->caldavBackend->getCalendarsForUser($this->principalInfo['uri']) as $calendar) {
            if ($calendar['uri'] === $name) {
                if ($this->caldavBackend instanceof Backend\SharingSupport) {
                    return new SharedCalendar($this->caldavBackend, $calendar);
                } else {
                    return new Calendar($this->caldavBackend, $calendar);
                }
            }
        }

        if ($this->caldavBackend instanceof Backend\SubscriptionSupport) {
            foreach ($this->caldavBackend->getSubscriptionsForUser($this->principalInfo['uri']) as $subscription) {
                if ($subscription['uri'] === $name) {
                    return new Subscriptions\Subscription($this->caldavBackend, $subscription);
                }
            }

        }

        throw new NotFound('Node with name \'' . $name . '\' could not be found');

    }

    
    function childExists($name) {

        try {
            return !!$this->getChild($name);
        } catch (NotFound $e) {
            return false;
        }

    }

    
    function getChildren() {

        $calendars = $this->caldavBackend->getCalendarsForUser($this->principalInfo['uri']);
        $objs = [];
        foreach ($calendars as $calendar) {
            if ($this->caldavBackend instanceof Backend\SharingSupport) {
                $objs[] = new SharedCalendar($this->caldavBackend, $calendar);
            } else {
                $objs[] = new Calendar($this->caldavBackend, $calendar);
            }
        }

        if ($this->caldavBackend instanceof Backend\SchedulingSupport) {
            $objs[] = new Schedule\Inbox($this->caldavBackend, $this->principalInfo['uri']);
            $objs[] = new Schedule\Outbox($this->principalInfo['uri']);
        }

                if ($this->caldavBackend instanceof Backend\NotificationSupport) {
            $objs[] = new Notifications\Collection($this->caldavBackend, $this->principalInfo['uri']);
        }

                if ($this->caldavBackend instanceof Backend\SubscriptionSupport) {
            foreach ($this->caldavBackend->getSubscriptionsForUser($this->principalInfo['uri']) as $subscription) {
                $objs[] = new Subscriptions\Subscription($this->caldavBackend, $subscription);
            }
        }

        return $objs;

    }

    
    function createExtendedCollection($name, MkCol $mkCol) {

        $isCalendar = false;
        $isSubscription = false;
        foreach ($mkCol->getResourceType() as $rt) {
            switch ($rt) {
                case '{DAV:}collection' :
                case '{http://calendarserver.org/ns/}shared-owner' :
                                        break;
                case '{urn:ietf:params:xml:ns:caldav}calendar' :
                    $isCalendar = true;
                    break;
                case '{http://calendarserver.org/ns/}subscribed' :
                    $isSubscription = true;
                    break;
                default :
                    throw new DAV\Exception\InvalidResourceType('Unknown resourceType: ' . $rt);
            }
        }

        $properties = $mkCol->getRemainingValues();
        $mkCol->setRemainingResultCode(201);

        if ($isSubscription) {
            if (!$this->caldavBackend instanceof Backend\SubscriptionSupport) {
                throw new DAV\Exception\InvalidResourceType('This backend does not support subscriptions');
            }
            $this->caldavBackend->createSubscription($this->principalInfo['uri'], $name, $properties);

        } elseif ($isCalendar) {
            $this->caldavBackend->createCalendar($this->principalInfo['uri'], $name, $properties);

        } else {
            throw new DAV\Exception\InvalidResourceType('You can only create calendars and subscriptions in this collection');

        }

    }

    
    function getOwner() {

        return $this->principalInfo['uri'];

    }

    
    function getACL() {

        return [
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->principalInfo['uri'],
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}write',
                'principal' => $this->principalInfo['uri'],
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->principalInfo['uri'] . '/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}write',
                'principal' => $this->principalInfo['uri'] . '/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->principalInfo['uri'] . '/calendar-proxy-read',
                'protected' => true,
            ],

        ];

    }


    
    function shareReply($href, $status, $calendarUri, $inReplyTo, $summary = null) {

        if (!$this->caldavBackend instanceof Backend\SharingSupport) {
            throw new DAV\Exception\NotImplemented('Sharing support is not implemented by this backend.');
        }

        return $this->caldavBackend->shareReply($href, $status, $calendarUri, $inReplyTo, $summary);

    }

    
    function getCalendarObjectByUID($uid) {

        return $this->caldavBackend->getCalendarObjectByUID($this->principalInfo['uri'], $uid);

    }

}
