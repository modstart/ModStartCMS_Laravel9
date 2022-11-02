<?php

namespace Sabre\DAV\Exception;

use Sabre\DAV;


class RequestedRangeNotSatisfiable extends DAV\Exception {

    
    function getHTTPCode() {

        return 416;

    }

}
