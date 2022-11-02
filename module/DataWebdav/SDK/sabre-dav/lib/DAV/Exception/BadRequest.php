<?php

namespace Sabre\DAV\Exception;

use Sabre\DAV;


class BadRequest extends DAV\Exception {

    
    function getHTTPCode() {

        return 400;

    }

}
