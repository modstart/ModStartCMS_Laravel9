<?php

namespace Sabre\CalDAV\Backend;


interface SyncSupport extends BackendInterface {

    
    function getChangesForCalendar($calendarId, $syncToken, $syncLevel, $limit = null);

}
