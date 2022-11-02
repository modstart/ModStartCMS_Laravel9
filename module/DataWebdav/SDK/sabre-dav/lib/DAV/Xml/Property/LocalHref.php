<?php

namespace Sabre\DAV\Xml\Property;

use Sabre\HTTP;


class LocalHref extends Href {

    
    function __construct($hrefs) {

        parent::__construct(array_map(
            function($href) {
                return \Sabre\HTTP\encodePath($href);
            },
            (array)$hrefs
        ));

    }

}
