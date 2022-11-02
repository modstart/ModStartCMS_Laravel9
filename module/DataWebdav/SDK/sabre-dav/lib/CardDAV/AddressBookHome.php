<?php

namespace Sabre\CardDAV;

use Sabre\DAV;
use Sabre\DAV\MkCol;
use Sabre\DAVACL;
use Sabre\Uri;


class AddressBookHome extends DAV\Collection implements DAV\IExtendedCollection, DAVACL\IACL {

    use DAVACL\ACLTrait;

    
    protected $principalUri;

    
    protected $carddavBackend;

    
    function __construct(Backend\BackendInterface $carddavBackend, $principalUri) {

        $this->carddavBackend = $carddavBackend;
        $this->principalUri = $principalUri;

    }

    
    function getName() {

        list(, $name) = Uri\split($this->principalUri);
        return $name;

    }

    
    function setName($name) {

        throw new DAV\Exception\MethodNotAllowed();

    }

    
    function delete() {

        throw new DAV\Exception\MethodNotAllowed();

    }

    
    function getLastModified() {

        return null;

    }

    
    function createFile($filename, $data = null) {

        throw new DAV\Exception\MethodNotAllowed('Creating new files in this collection is not supported');

    }

    
    function createDirectory($filename) {

        throw new DAV\Exception\MethodNotAllowed('Creating new collections in this collection is not supported');

    }

    
    function getChild($name) {

        foreach ($this->getChildren() as $child) {
            if ($name == $child->getName())
                return $child;

        }
        throw new DAV\Exception\NotFound('Addressbook with name \'' . $name . '\' could not be found');

    }

    
    function getChildren() {

        $addressbooks = $this->carddavBackend->getAddressBooksForUser($this->principalUri);
        $objs = [];
        foreach ($addressbooks as $addressbook) {
            $objs[] = new AddressBook($this->carddavBackend, $addressbook);
        }
        return $objs;

    }

    
    function createExtendedCollection($name, MkCol $mkCol) {

        if (!$mkCol->hasResourceType('{' . Plugin::NS_CARDDAV . '}addressbook')) {
            throw new DAV\Exception\InvalidResourceType('Unknown resourceType for this collection');
        }
        $properties = $mkCol->getRemainingValues();
        $mkCol->setRemainingResultCode(201);
        $this->carddavBackend->createAddressBook($this->principalUri, $name, $properties);

    }

    
    function getOwner() {

        return $this->principalUri;

    }

}
