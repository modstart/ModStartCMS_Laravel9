<?php

namespace Sabre\CalDAV\Backend;


interface SchedulingSupport extends BackendInterface {

    
    function getSchedulingObject($principalUri, $objectUri);

    
    function getSchedulingObjects($principalUri);

    
    function deleteSchedulingObject($principalUri, $objectUri);

    
    function createSchedulingObject($principalUri, $objectUri, $objectData);

}
