<?php

namespace Sabre\DAV\Exception;

use Sabre\DAV;


class NotAuthenticated extends DAV\Exception {

    
    function getHTTPCode() {

        return 401;

    }

}
