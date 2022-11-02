<?php

namespace Sabre\CardDAV\Backend;


interface SyncSupport extends BackendInterface {

    
    function getChangesForAddressBook($addressBookId, $syncToken, $syncLevel, $limit = null);

}
