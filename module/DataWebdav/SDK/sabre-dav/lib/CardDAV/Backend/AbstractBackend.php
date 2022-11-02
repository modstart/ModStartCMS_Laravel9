<?php

namespace Sabre\CardDAV\Backend;


abstract class AbstractBackend implements BackendInterface {

    
    function getMultipleCards($addressBookId, array $uris) {

        return array_map(function($uri) use ($addressBookId) {
            return $this->getCard($addressBookId, $uri);
        }, $uris);

    }

}
