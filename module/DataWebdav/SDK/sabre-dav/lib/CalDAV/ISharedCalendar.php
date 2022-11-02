<?php

namespace Sabre\CalDAV;

use Sabre\DAV\Sharing\ISharedNode;


interface ISharedCalendar extends ISharedNode {

    
    function setPublishStatus($value);
}
