<?php

namespace Sabre\CalDAV\Subscriptions;

use Sabre\CalDAV\Backend\SubscriptionSupport;
use Sabre\DAV\Collection;
use Sabre\DAV\PropPatch;
use Sabre\DAV\Xml\Property\Href;
use Sabre\DAVACL\ACLTrait;
use Sabre\DAVACL\IACL;


class Subscription extends Collection implements ISubscription, IACL {

    use ACLTrait;

    
    protected $caldavBackend;

    
    protected $subscriptionInfo;

    
    function __construct(SubscriptionSupport $caldavBackend, array $subscriptionInfo) {

        $this->caldavBackend = $caldavBackend;
        $this->subscriptionInfo = $subscriptionInfo;

        $required = [
            'id',
            'uri',
            'principaluri',
            'source',
            ];

        foreach ($required as $r) {
            if (!isset($subscriptionInfo[$r])) {
                throw new \InvalidArgumentException('The ' . $r . ' field is required when creating a subscription node');
            }
        }

    }

    
    function getName() {

        return $this->subscriptionInfo['uri'];

    }

    
    function getLastModified() {

        if (isset($this->subscriptionInfo['lastmodified'])) {
            return $this->subscriptionInfo['lastmodified'];
        }

    }

    
    function delete() {

        $this->caldavBackend->deleteSubscription(
            $this->subscriptionInfo['id']
        );

    }

    
    function getChildren() {

        return [];

    }

    
    function propPatch(PropPatch $propPatch) {

        return $this->caldavBackend->updateSubscription(
            $this->subscriptionInfo['id'],
            $propPatch
        );

    }

    
    function getProperties($properties) {

        $r = [];

        foreach ($properties as $prop) {

            switch ($prop) {
                case '{http://calendarserver.org/ns/}source' :
                    $r[$prop] = new Href($this->subscriptionInfo['source']);
                    break;
                default :
                    if (array_key_exists($prop, $this->subscriptionInfo)) {
                        $r[$prop] = $this->subscriptionInfo[$prop];
                    }
                    break;
            }

        }

        return $r;

    }

    
    function getOwner() {

        return $this->subscriptionInfo['principaluri'];

    }

    
    function getACL() {

        return [
            [
                'privilege' => '{DAV:}all',
                'principal' => $this->getOwner(),
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}all',
                'principal' => $this->getOwner() . '/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->getOwner() . '/calendar-proxy-read',
                'protected' => true,
            ]
        ];

    }

}
