<?php

namespace Sabre\DAV\Exception;

use Sabre\DAV;


class Forbidden extends DAV\Exception {

    
    function getHTTPCode() {

        return 403;

    }

}
