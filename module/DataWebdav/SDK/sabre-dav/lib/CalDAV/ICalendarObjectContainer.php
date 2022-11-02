<?php

namespace Sabre\CalDAV;


interface ICalendarObjectContainer extends \Sabre\DAV\ICollection {

    
    function calendarQuery(array $filters);

}
