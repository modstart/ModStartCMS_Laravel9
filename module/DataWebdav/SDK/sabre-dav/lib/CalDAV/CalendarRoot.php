<?php

namespace Sabre\CalDAV;

use Sabre\DAVACL\PrincipalBackend;


class CalendarRoot extends \Sabre\DAVACL\AbstractPrincipalCollection {

    
    protected $caldavBackend;

    
    function __construct(PrincipalBackend\BackendInterface $principalBackend, Backend\BackendInterface $caldavBackend, $principalPrefix = 'principals') {

        parent::__construct($principalBackend, $principalPrefix);
        $this->caldavBackend = $caldavBackend;

    }

    
    function getName() {

        return Plugin::CALENDAR_ROOT;

    }

    
    function getChildForPrincipal(array $principal) {

        return new CalendarHome($this->caldavBackend, $principal);

    }

}
