<?php

namespace Sabre\CalDAV\Notifications;

use Sabre\CalDAV;
use Sabre\CalDAV\Xml\Notification\NotificationInterface;
use Sabre\DAV;
use Sabre\DAVACL;


class Node extends DAV\File implements INode, DAVACL\IACL {

    use DAVACL\ACLTrait;

    
    protected $caldavBackend;

    
    protected $notification;

    
    protected $principalUri;

    
    function __construct(CalDAV\Backend\NotificationSupport $caldavBackend, $principalUri, NotificationInterface $notification) {

        $this->caldavBackend = $caldavBackend;
        $this->principalUri = $principalUri;
        $this->notification = $notification;

    }

    
    function getName() {

        return $this->notification->getId() . '.xml';

    }

    
    function getETag() {

        return $this->notification->getETag();

    }

    
    function getNotificationType() {

        return $this->notification;

    }

    
    function delete() {

        $this->caldavBackend->deleteNotification($this->getOwner(), $this->notification);

    }

    
    function getOwner() {

        return $this->principalUri;

    }

}
