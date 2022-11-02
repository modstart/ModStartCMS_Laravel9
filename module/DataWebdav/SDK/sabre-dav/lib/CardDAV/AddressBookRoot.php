<?php

namespace Sabre\CardDAV;

use Sabre\DAVACL;


class AddressBookRoot extends DAVACL\AbstractPrincipalCollection {

    
    protected $principalBackend;

    
    protected $carddavBackend;

    
    function __construct(DAVACL\PrincipalBackend\BackendInterface $principalBackend, Backend\BackendInterface $carddavBackend, $principalPrefix = 'principals') {

        $this->carddavBackend = $carddavBackend;
        parent::__construct($principalBackend, $principalPrefix);

    }

    
    function getName() {

        return Plugin::ADDRESSBOOK_ROOT;

    }

    
    function getChildForPrincipal(array $principal) {

        return new AddressBookHome($this->carddavBackend, $principal['uri']);

    }

}
