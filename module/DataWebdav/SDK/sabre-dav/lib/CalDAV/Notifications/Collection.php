<?php

namespace Sabre\CalDAV\Notifications;

use Sabre\CalDAV;
use Sabre\DAV;
use Sabre\DAVACL;


class Collection extends DAV\Collection implements ICollection, DAVACL\IACL {

    use DAVACL\ACLTrait;

    
    protected $caldavBackend;

    
    protected $principalUri;

    
    function __construct(CalDAV\Backend\NotificationSupport $caldavBackend, $principalUri) {

        $this->caldavBackend = $caldavBackend;
        $this->principalUri = $principalUri;

    }

    
    function getChildren() {

        $children = [];
        $notifications = $this->caldavBackend->getNotificationsForPrincipal($this->principalUri);

        foreach ($notifications as $notification) {

            $children[] = new Node(
                $this->caldavBackend,
                $this->principalUri,
                $notification
            );
        }

        return $children;

    }

    
    function getName() {

        return 'notifications';

    }

    
    function getOwner() {

        return $this->principalUri;

    }

}
