<?php

namespace Sabre\CalDAV\Backend;


interface SharingSupport extends BackendInterface {

    
    function updateInvites($calendarId, array $sharees);

    
    function getInvites($calendarId);

    
    function setPublishStatus($calendarId, $value);

}
