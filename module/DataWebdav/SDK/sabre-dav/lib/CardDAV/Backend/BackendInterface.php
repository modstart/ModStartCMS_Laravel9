<?php

namespace Sabre\CardDAV\Backend;


interface BackendInterface {

    
    function getAddressBooksForUser($principalUri);

    
    function updateAddressBook($addressBookId, \Sabre\DAV\PropPatch $propPatch);

    
    function createAddressBook($principalUri, $url, array $properties);

    
    function deleteAddressBook($addressBookId);

    
    function getCards($addressbookId);

    
    function getCard($addressBookId, $cardUri);

    
    function getMultipleCards($addressBookId, array $uris);

    
    function createCard($addressBookId, $cardUri, $cardData);

    
    function updateCard($addressBookId, $cardUri, $cardData);

    
    function deleteCard($addressBookId, $cardUri);

}
