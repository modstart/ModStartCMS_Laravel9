<?php

namespace Sabre\CalDAV\Schedule;

use Sabre\CalDAV;
use Sabre\DAV;
use Sabre\DAVACL;


class Outbox extends DAV\Collection implements IOutbox {

    use DAVACL\ACLTrait;

    
    protected $principalUri;

    
    function __construct($principalUri) {

        $this->principalUri = $principalUri;

    }

    
    function getName() {

        return 'outbox';

    }

    
    function getChildren() {

        return [];

    }

    
    function getOwner() {

        return $this->principalUri;

    }

    
    function getACL() {

        return [
            [
                'privilege' => '{' . CalDAV\Plugin::NS_CALDAV . '}schedule-send',
                'principal' => $this->getOwner(),
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->getOwner(),
                'protected' => true,
            ],
            [
                'privilege' => '{' . CalDAV\Plugin::NS_CALDAV . '}schedule-send',
                'principal' => $this->getOwner() . '/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->getOwner() . '/calendar-proxy-read',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->getOwner() . '/calendar-proxy-write',
                'protected' => true,
            ],
        ];

    }

}
