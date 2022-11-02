<?php

namespace Sabre\DAV\Exception;

use Sabre\DAV;


class UnsupportedMediaType extends DAV\Exception {

    
    function getHTTPCode() {

        return 415;

    }

}
