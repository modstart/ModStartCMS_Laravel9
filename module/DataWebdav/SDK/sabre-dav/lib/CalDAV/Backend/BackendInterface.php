<?php

namespace Sabre\CalDAV\Backend;


interface BackendInterface {

    
    function getCalendarsForUser($principalUri);

    
    function createCalendar($principalUri, $calendarUri, array $properties);

    
    function updateCalendar($calendarId, \Sabre\DAV\PropPatch $propPatch);

    
    function deleteCalendar($calendarId);

    
    function getCalendarObjects($calendarId);

    
    function getCalendarObject($calendarId, $objectUri);

    
    function getMultipleCalendarObjects($calendarId, array $uris);

    
    function createCalendarObject($calendarId, $objectUri, $calendarData);

    
    function updateCalendarObject($calendarId, $objectUri, $calendarData);

    
    function deleteCalendarObject($calendarId, $objectUri);

    
    function calendarQuery($calendarId, array $filters);

    
    function getCalendarObjectByUID($principalUri, $uid);

}
