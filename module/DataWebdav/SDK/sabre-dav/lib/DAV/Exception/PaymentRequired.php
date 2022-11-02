<?php

namespace Sabre\DAV\Exception;

use Sabre\DAV;


class PaymentRequired extends DAV\Exception {

    
    function getHTTPCode() {

        return 402;

    }

}
