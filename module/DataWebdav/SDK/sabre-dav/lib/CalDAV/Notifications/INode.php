<?php

namespace Sabre\CalDAV\Notifications;

use Sabre\CalDAV\Xml\Notification\NotificationInterface;


interface INode {

    
    function getNotificationType();

    
    function getETag();

}
