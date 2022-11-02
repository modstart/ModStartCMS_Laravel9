<?php

namespace Sabre\DAV\Exception;

use Sabre\DAV;


class ServiceUnavailable extends DAV\Exception {

    
    function getHTTPCode() {

        return 503;

    }

}
