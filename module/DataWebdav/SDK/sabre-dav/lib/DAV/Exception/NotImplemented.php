<?php

namespace Sabre\DAV\Exception;

use Sabre\DAV;


class NotImplemented extends DAV\Exception {

    
    function getHTTPCode() {

        return 501;

    }

}
