<?php

namespace Sabre\DAV\Exception;

use Sabre\DAV;


class LengthRequired extends DAV\Exception {

    
    function getHTTPCode() {

        return 411;

    }

}
