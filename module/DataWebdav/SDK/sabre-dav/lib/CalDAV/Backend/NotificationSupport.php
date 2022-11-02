<?php

namespace Sabre\CalDAV\Backend;

use Sabre\CalDAV\Xml\Notification\NotificationInterface;


interface NotificationSupport extends BackendInterface {

    
    function getNotificationsForPrincipal($principalUri);

    
    function deleteNotification($principalUri, NotificationInterface $notification);

    
    function shareReply($href, $status, $calendarUri, $inReplyTo, $summary = null);

}
