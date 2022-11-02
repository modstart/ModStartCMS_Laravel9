<?php

namespace Sabre\DAV\Exception;

use Sabre\DAV;


class Conflict extends DAV\Exception {

    
    function getHTTPCode() {

        return 409;

    }

}
